# Symfony rich console
This component integrates both *Symfony Dependency Injection* and *Symfony Event Dispatcher* components into *Symfony Console* applications.

> Note: This is only intended if dealing with *Symfony Console* **standalone** applications, not web framework ones.

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/rodrigodiez/symfony-rich-console/badges/quality-score.png?s=13eb32ae317f50cf5bdd5a166554dd72e0b21cdc)](https://scrutinizer-ci.com/g/rodrigodiez/symfony-rich-console/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a8297e5f-7fd1-4974-b0f0-cfcbc6a28917/mini.png)](https://insight.sensiolabs.com/projects/a8297e5f-7fd1-4974-b0f0-cfcbc6a28917)

## Example:

```php
    public function run(InputInterface $input, OutputInterface $output)
    {
        // You can access services
        $myService = $this->container->get('my_service');
        $input->writeln('My service says ' . $myService->hello());

        // You can get parameters
        $myParam = $this->container->getParameter('my_param');

        // You can dispatch events and these will be received by their listeners / subscribers
        $event = new Event();
        $this->container->get('event_dispatcher')->dispatch('custom.event', $event);
        $input->writeln('My listeners says ' . $event->getValue());
    }
```

## Installation
### 1. Download it using composer
Add `rodrigodiez/symfony-rich-console` to your `composer.json`

```js
{
    "require": {
        "rodrigodiez/symfony-rich-console": "dev-master"
    }
}
```

Now, tell composer to download the component by typing

```bash
$ php composer.phar update rodrigodiez/symfony-rich-console
```

### 2. Create a console
You need a entry point file to instantiate and run your application. You can create it at `app/console`.

```php
#!/usr/bin/env php

<?php
use Rodrigodiez\Component\RichConsole\Application;

require_once('vendor/autoload.php');

$app = new Application();
$app->run();
```

> Note that you **must** extend the custom Application class provided within this component.

The Application class constructor receives two **optional** parameters:

- **configPath**: String containing the config path. The application will try to find here the required `parameters.yml` file and other configuration files. Defaults to `app/config`.
- **configFilenames**: Array of file names located in `$configPath` which you want to be loaded into the *container*. Ej: `array('services.yml')`. You typically will define your *commands*, *services*, *listeners*, *subscribers*, etc in these files.

### 2. Create a `parameters.yml` file
This file is **mandatory**, it **must** be located in your `configPath` and it **must** contain, at least, the following info:

```yaml
parameters:
    application.info:
        name: your_aplication_name
        version: your_application_version
```

### 3. Done!
Now you can execute your app by typing...

```bash
$php app/console
```

... but the result may be disappointing. This is because we didn't yet registered any commands into the application.

## Adding a configuration file
To be able to define your services (commands are defined as services too) it is necessary to create a configuration file in `configPath` and tell the application to load it:

```php
//app/console

//...
$app = new Application(null, array('services.yml'));
//...
```

## Registering commands
Simply register your command as a service and tag it as `console.command`.

```yaml
# app/config/services.yml
services:
    command_service:
        class: Your\Namespace\YourCommand
        tags:
            - { name: console.command }
```

If your command class implements `Symfony\\Component\\DependencyInjection\\ContainerAwareInterface` then container will be injected and you can retrieve it through its `$container` property.

## Registering listeners and subscribers

```yaml
# app/config/services.yml
services:
    listener_service:
        class: Your\Namespace\YourListener
        tags:
            - { name: kernel.event_listener, method: onEventMethod }

    subscriber_service:
        class: Your\Namespace\YourSubscriber
        tags:
            - { name: kernel.event_subscriber }
```

## That is all!
I hope this to be useful to somebody. Comments, issue reports and pull requests will be appreciated :)

