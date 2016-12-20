#SSL Configuration

For wss:// connections we recommend using stunnel. It is used to open a secured port and then forward it to a not secured port on the same other different machine. You can also use Nginx or HaProxy

##Using stunnel

Install [Stunnel](https://www.stunnel.org/index.html) : 

```cmd
 sudo apt-get install stunnel
 ```

Create a config file in /etc/stunnel/. Preferably named stunnel.conf : 

```cmd
nano /etc/stunnel/stunnel.conf
```

```ini
# Certificate
cert = /my/way/to/ssl.crt
key = /my/way/to/not_crypted.key

# Remove TCP delay for local and remote.
socket = l:TCP_NODELAY=1
socket = r:TCP_NODELAY=1

chroot = /var/run/stunnel4/
pid = /stunnel.pid

# Only use this options if for making it more secure after you get it to work.
# User id
#setuid = nobody
# Group id
#setgid = nobody

# IMPORTANT: If the websocketserver is on the same server as the webserver use this:
#local = my.domainname.com # Insert here your domain that is secured with https.

[websockets]
accept = 8443
connect = 8888
# IMPORTANT: If you use the local variable above, you have to add the domainname here aswell.
# connect = my.domainname.com:8888 
# ALSO *: When starting your websocket server, you have to use the -a parameter to specify the domainname
```


(*) Starting the websocketserver when on same server :

```cmd
php app/console gos:websocket:server -a my.domainname.com -e=prod -n
```


Save the file and start stunnel : 

 ```cmd
/etc/init.d/stunnel4 start
```


For running stunnel automated edit properties in /etc/default/stunnel4:

```ini
ENABLED=1
```

## Using Nginx

Create a folder named `stream.d` at the root of your nginx install (for Debian: `/etc/nginx`) 
Create the file `websocket.conf` inside the folder you've just created, and copy / adjust the following content.
```
stream {
    upstream websocket_backend {
        server YOUR-LOCAL-IP:RUNNING-PORT;
    }
    server {
        listen ACCEPT-PORT ssl;
        proxy_pass websocket_backend;
        proxy_timeout 4h; # adjust it to your needs

        ssl_certificate /my/way/to/ssl.crt;
        ssl_certificate_key /my/way/to/not_crypted.key;

        ssl_handshake_timeout 5s; # adjust it to your needs
        proxy_buffer_size 16k;
        ssl_session_timeout 4h; # adjust it to your needs
    }
}
```

Then, edit `nginx.conf` located in `/etc/nginx`

After the `http` directive, place the following statement `include /etc/nginx/stream.d/*.conf;`
It has to be at the same hierarchy level of `http`.
This configuration was tested on nginx 1.10 but it should works on nginx 1.9 as well.

Execute `/etc/init.d/nginx reload` and run your websocket server using the following command line `bin/console gos:websocket:server --port RUNNING-PORT -a YOUR-LOCAL-IP`


## Finally

Launch your websocket server in the *connectport*.

Connect the client on wss://my.domainname.com:*acceptport*

