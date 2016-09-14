#SSL Configuration

For wss:// connections we recommend using stunnel. It is used to open a secured port and then forward it to a not secured port on the same other different machine.

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


Launch your websocket server in the *connectport*.

Connect the client on wss://my.domainname.com:*acceptport*

