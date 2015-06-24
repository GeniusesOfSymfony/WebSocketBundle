#Ship in production

How run your websocket server in production ?
------------------------------------

```cmd
app/console gos:websocket:server --env=prod
```

**Example with supervisord and other things will come**

Fight against memory leak !
---------------------------

So why my memory increase all time ? 

- In development mode it's normal. (Don't bench memory leaks in this env, never) append your command with `--env=prod` 
- Are you using `fingers_crossed` handler with monolog ? If yes, switch to stream. That's `fingers_crossed` expected behavior. It stores log entries in memory until event of `action_level` occurs.
- Dependencies of this bundle can have some troubles :( (But I can't do nothing, and if it's the case, downgrade or freeze impacted dependency)
- It's your fault :) Dig in you own code.
 

How bench about memory leaks ? 
------------------------------

```cmd
app/console gos:websocket:server --profile --env=prod
```

And trigger all the things.



