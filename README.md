# Cron Tasks Extension for Symphony CMS

-   Version: 2.0.0
-   Date: June 09 2019
-   [Release notes](https://github.com/pointybeard/cron/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/cron)

An extension for automating tasks via the Symphony CMS Console extension

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, run `composer update` to install required packages and then enable it though the interface.

### Requirements

This extension requires PHP 7.3 or greater. For use with earlier version of PHP, please use v1.1.0 of this extension instead (`git clone -b1.1.0 https://github.com/pointybeard/cron.git`).

The [Console Extension for Symphony CMS](https://github.com/pointybeard/console) must also be installed.

This extension depends on the following Composer libraries:

-   [SymphonyCMS PDO Connector](https://github.com/pointybeard/symphony-pdo)
-   [PHP Helpers](https://github.com/pointybeard/helpers)

Run `composer install` on the `extension/cron` directory to install all of these.

### Setup

(Optional) Create a crontab entry (`crontab -e` on most *nix setups) that executes the command `symphony -t xxxx cron tasks run` where `xxxx` is a author login token. E.g. this will run the console tasks command every 1 minute and save the output to `/logs/symphony-cron.log`

    */1 * * * * SYMPHONY_DOCROOT=/path/to/symphony /absolute/path/to/console/bin/symphony -- -t e1f8781e cron tasks run >> /logs/symphony-cron.log 2>&1

## Usage

Use the 'Cron' interface in the Admin, found under the 'System' menu, to create and edit tasks. Newly created tasks are stored in `/manifest/cron`, making them suitable for version control.

Anytime the `tasks` command is executed via the command-line, all pending tasks (those due for execution) will be run. This process can be automated with an entry in your server's `crontab` (see Setup above).

## Creating a task manually

1. Create a file with a unique name and extension of `.task` (e.g. `mytask.task`) and place it in `/manifest/cron`

2. The contents must be valid JSON:

```json
{
    "name": "I am a nice name",
    "command": "ls -lah",
    "description": "I am an optional description",
    "interval": {
        "type": "(minute|hour|day|week)",
        "duration": "2"
    },
    "start": "2009-08-21 07:45am",
    "finish": null
}
```
3. Browse to `System > Cron` in the Symphony Administration

4. Enable the newly created Cron task

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/cron/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/cron/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Cron Tasks Extension for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
