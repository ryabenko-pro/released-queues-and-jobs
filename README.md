# Queues and Jobs

> **This is not a message queue!**

Queues and Jobs is a framework for building scalable applications. Scalability is achieved thru moving heavy and risky operations to be executed 
asynchronously in separate thread.

# Queues

Queues adds simple move-to-background functionality which may be useful in many cases. In modern world most of operations can be asynchronous and what 
can be asynchronous **must be asynchronous**.

### Case 1: Shopping Cart

When user clicks on checkout cart this does not mean order must be placed exact same second. 
Instead we can mark cart as processing and plan a task to make checkout in background. User will usually not notice a difference between synchronous 
and asynchronous checkout, but if something will go wrong during checkout process in synchronous process user will see error and a customer may be lost,
while in asynchronous manner failed operation may be replayed and only result in delay in order processing. This is also bad but not as critical as 
left card.  

### Case 2: Business flow

When you have complex business flow that depends on external services you might get whole process failed because of a single failed step. To solve 
this you might split your flow to independent steps and execute each of them sequentially or in parallel.

For example lets take a card checkout when for each item you need place a quote, validate order on partner service and if everything ok place an order 
and notify partner service about this order.

If you do all operations in the same thread each step can break system due to temporary issue on 3rd party API and you must be ready to restart a 
process (by loosing previous quote) or catch it where it break. 
  
Instead you can plan a task for each to be executed sequentially. When any step fails you simply restart it (if not done automatically) and 
flow goes like nothing happens.

## Configuration

```yaml
released_queue:
    template: ::base.html.twig  # optional  
    doctrine.orm.default_entity_manager: default # optional
    types:
        echo: # Must be same as `getType` return value
            name: Example echo task
            class_name: Released\QueueBundle\Model\EchoTask
```
                
> If you are getting `Type 'your_task' not found` exception most likely you forgot to define task in `config.yml`.  

## Usage

Usage is very simple. To create a queueable task you need just to extend class from `BaseTask` and implement abstract methods. After you do that you can plan a task 
using `released.queue.task_queue.service` service: 

```php
<?php
    $queue = $container->get('released.queue.task_queue.service');
    
    $task1 = new CreateQuoteTask(['order_id' => $order->getId()]);
    $queue->addTask($task1); // Plan task execution 
    
    // This task will only start after previous is successfully completed
    $task2 = new ValidateQuoteTask(['order_id' => $order->getId(), $task1);
    $queue->addTask($task2);
```
        
Implemented `execute` method have Symfony container. See `EchoTask` for example.  

To run enqueued tasks you need to run command in console:

```console
./bin/console released:queue:execute-task --permanent --cycles-limit=10 --cycle-delay=5 --memory-limit=XXX --single-id=THREAD_ID
```

Where:
- `--permanent` do not exit command immediately but stay alive and wait for new tasks
- `--cycles-limit` specify the cycles limit once reached task will exit. Keep in mind the **cycle** increased on every queue check 
even if no task executed
- `--cycle-delay` delay in seconds between queue check
- `--memory-limit` memory limit in bytes once reached task will exit
- `--single-id` is used to run multiple tasks with the same name. More explained below.

By default only one instance of this task can be run. Every time you run this command it will check for running command and exit if there is one.
When you need to run more then one task you can specify a `--single-id` option. Value does not matter it just must be different for each run attempt.  
This is done so you could add this task to a cron to run every minute and be confident only 1 task with specific `single-id` is running at a time. 

> By default cache directory is used to keep task pids, this is not secure as it may be removed and pid will be lost. It is recommended to specify 
`--pid-dir=path/to/dir/with/pids/` option to keep pids in safe place. 

# Jobs 

Jobs are used when amount of work can't be determined upfront. For example fetch data for all products in database. When you have just a few products 
updating them in look will work, but with products amount grow code will run longer and you will have less control.

With jobs you as a first step you plan a work to do creating tasks, then each task executed and after all tasks are done job is finished. 
When you have too much items to process and planning step takes too much time or memory you can plan a chunk of tasks until everything is planned. 

> More description will be provided later  