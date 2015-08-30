<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Gos\Component\PnctlEventLoopEmitter\PnctlEmitter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ratchet\Http\Guzzle\Http\Message\RequestFactory;
use Ratchet\Http\HttpRequestParser;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\Socket\Server;
use React\Stream\ReadableStreamInterface;
use React\Stream\Stream;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClusterCommand extends Command
{
    /**
     * @var EntryPoint
     */
    protected $entryPoint;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $host;

    protected $connections;

    protected $upstreamOrder;

    protected $upstreamPool;

    protected $upstreamProcess;

    protected $masterStream;

    protected $buffer;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param EntryPoint      $entryPoint
     * @param string          $host
     * @param int             $port
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntryPoint $entryPoint,
        $host,
        $port,
        LoggerInterface $logger = null
    ) {
        $this->entryPoint = $entryPoint;
        $this->port = (int) $port;
        $this->host = $host;
        $this->logger = null === $logger ? new NullLogger() : $logger;
        $this->connections = [];
        $this->upstreamPool = [];
        $this->upstreamOrder = [];
        $this->upstreamProcess = [];
        $this->buffer = '';

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('gos:websocket:cluster')
            ->setDescription('Starts the web socket servers')
            ->addArgument('ports', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Servers ports');
    }

    protected function shutdown()
    {
        foreach($this->upstreamProcess as $sign => $process){
            $this->logger->info(sprintf('Websocket stopped #%s', $this->getUpstreamIdentifier($sign)));
            $process->close();
        }
    }

    /**
     * @param string $host
     * @param int $port
     *
     * @return string
     */
    protected function addUpstream($host, $port)
    {
        $this->upstreamPool[$sign = sha1($host.$port)] = $host.':'.$port;
        $this->upstreamOrder[$sign] = $order = count($this->upstreamOrder) + 1;

        return $sign;
    }

    /**
     * @param string $sign
     *
     * @return int
     */
    protected function getUpstreamIdentifier($sign)
    {
        return $this->upstreamOrder[$sign];
    }

    /**
     * @param string $sign
     *
     * @return array
     */
    protected function getUpstream($sign)
    {
        return $this->upstreamPool[$sign];
    }

    /**
     * @param ReadableStreamInterface $source
     * @param WritableStreamInterface $dest
     * @param array                   $options
     */
    protected function pipe(
        ReadableStreamInterface $source,
        WritableStreamInterface $dest,
        array $options = array()
    ) {
        $dest->emit('pipe', array($source));

        $source->on('data', function ($data) use ($source, $dest, $options) {

            if((string) $source->stream !== $this->masterStream){
                if((bool) strpos($data, "\r\n\r\n")){
                    $buffer = '';
                    foreach(explode("\r\n", $data) as $line){
                        if('HTTP/1.1 101 Switching Protocols' === $line){
                            $buffer .= $line."\r\n";
                            $buffer .= 'Via: 1.0 Gos'."\r\n";
                            $buffer .= 'X-Websocket-Server: '.$options['sign'];
                        }else{
                            $buffer .= $line."\r\n";
                        }
                    }

                    $data = $buffer;
                }
            }

            $feedMore = $dest->write($data);

            if (false === $feedMore) {
                $source->pause();
            }
        });

        $dest->on('drain', function () use ($source) {
            $source->resume();
        });

        $end = isset($options['end']) ? $options['end'] : true;
        if ($end && $source !== $dest) {
            $source->on('end', function () use ($dest) {
                $dest->end();
            });
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ports = $input->getArgument('ports');
        $colors = [ 'red', 'yellow', 'green', 'blue', 'magenta', 'cyan', 'white' ];

        $formatter = $this->getHelperSet()->get('formatter');

        $loop = Factory::create();
        $socket = new Server($loop);
        $signalHandler = new PnctlEmitter($loop);

        //Setup and connect upstream
        foreach($ports as $port){
            $process = new Process('exec php app/console gos:websocket:server -p '.$port.' --profile');
            $process->start($loop);

            $sign = $this->addUpstream($this->host, $port);
            $identifier = $this->getUpstreamIdentifier($sign);
            $this->upstreamProcess[$identifier] = $process;

            $output->getFormatter()->setStyle('ws'.$identifier, new OutputFormatterStyle($colors[array_rand($colors)]));

            $process->stdout->on('data', function($data) use ($output, $identifier, $formatter) {
                $output->write(sprintf('<%s>WS#%s |</%s> %s', 'ws'.$identifier, $identifier, 'ws'.$identifier, $data));
            });

            $process->stderr->on('data', function($data) use ($output, $identifier) {
                $output->write(sprintf('<%s>WS#%s</%s> <error>|</error> %s', 'ws'.$identifier, $identifier, 'ws'.$identifier, $data));
            });

            $this->logger->info(sprintf('Websocket #%s started', $identifier));
        }

        $socket->on('error', function($e){
            dump($e);
        });

        $loop->addTimer(0.01, function($timer) use ($socket){
            $loop = $timer->getLoop();

            $socket->on('connection', function($conn) use ($loop){
                $this->masterStream = (string) $conn->stream;

                $sign = array_rand($this->upstreamPool);
                list($host, $port) = explode(':', $this->upstreamPool[$sign]);

                $upstream = new Stream(stream_socket_client('tcp://'.$host.':'.$port), $loop);

                //full duplex
                $this->pipe($conn, $upstream);
                $this->pipe($upstream, $conn, ['sign' => $sign]);

                $this->logger->info('Client connected to WS#'.$this->getUpstreamIdentifier($sign));
            });
        });

        $signalHandler->on(SIGINT, function () use ($loop) {
            $this->shutdown();
            $loop->stop();
        });

        $signalHandler->on(SIGTERM, function() use ($loop){
            $this->shutdown();
            $loop->stop();
        });

//        $signalHandler->on(SIGKILL, function() use ($loop){
//            $proc = new \Symfony\Component\Process\Process('kill $(ps aux | grep \'gos:websocket:\' | awk \'{print $2}\')');
//            $proc->run(function() use ($loop){
//                $loop->stop();
//            });
//        });

        $socket->listen($this->port, $this->host);
        $loop->run();
    }
}
