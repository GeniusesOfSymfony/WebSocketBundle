#SSL Configuration

Install [Stunnel](https://www.stunnel.org/index.html) : 

```cmd
 sudo apt-get install stunnel
 ```

Update configuration such as following : 

```cmd
nano /etc/stunnel/myconf.conf
```

```ini
# Certificate
cert = /my/way/to/ssl.crt
key = /my/way/to/not_crypted.key

chroot = /var/run/stunnel4/
pid = /stunnel.pid

# User id
setuid = nobody

# Group id
#setgid = nobody

[websockets]
accept = 8443
connect = 8888
```

Save the file and start stunnel : 

 ```cmd
/etc/init.d/stunnel4 start
```

Launch your websocket server in the connect port and connect in the client view on wss://mysite:acceptport

