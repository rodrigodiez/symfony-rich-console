# Symfony rich console
This component **integrates both *Symfony Dependency Injection Container* and *Symfony Event Dispatcher* into *Symfony Console* component**. This way you can define and use your own *parameters*, *services*, *event listeners*, *event subscribers*, etc on your standalone console applications.

> Note: This is only intended for using with *Symfony Console* **standalone** applications, not web framework ones.


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

It's a *Symfony Console* with steroids!

## Installation
### Download it using composer
Add `rodrigodiez/symfony-rich-console` to your `composer.json`

```js
{
    "require": {
        "rodrigodiez/symfony-rich-console": "dev-master"
    }
}
``

### Create a console
You need a entry point file to instantiate and run your application. You can create it at `app/console`.

```php
#!/usr/bin/env php

<?php
use Rodrigodiez\Component\RichConsole\Application;

require_once('vendor/autoload.php');

$app = new Application(null, array('services.yml'));
$app->run();
```

> Note that you **must** extend the custom Application class provided within this component.

The Application class constructor receives two **optional** parameters:

    - `$configPath`: The application will try to find here the required `parameters.yml` file and other config files. Defaults to `app/config`.
    - `$configFilenames`: Array of file names located in `$configPath` which you want to be loaded into the *container*. Ej: `array('services.yml')`. You typically will define your *commands*, *services*, *listeners*, *subscribers*, etc in these files.

### Create a `parameters.yml file`
This file is **mandatory** and it **must** contain, at least, following info:

```yaml
parameters:
    application.info:
        name: your_aplication_name
        version: your_application_version
```

