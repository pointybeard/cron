# Cron Tasks Extension for Symphony CMS

-   Version: 2.0.0
-   Date: June 12 2019
-   [Release notes](https://github.com/pointybeard/cron/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/cron)

An extension for automating tasks via the Symphony CMS Console extension

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, run `composer update` to install required packages and then enable it though the interface.

### Requirements

This extension requires PHP 7.3 or greater. For use with earlier version of PHP, please use v1.1.1 of this extension instead (`git clone -b1.1.1 https://github.com/pointybeard/cron.git`).

The [Console Extension for Symphony CMS](https://github.com/pointybeard/console) must also be installed.

This extension depends on the following Composer libraries:

-   [SymphonyCMS PDO Connector](https://github.com/pointybeard/symphony-pdo)
-   [PHP Helpers](https://github.com/pointybeard/helpers)

Run `composer update` on the `extension/cron` directory to install these.

### Setup

(Optional) Create a crontab entry (`crontab -e` on most *nix setups) that executes the command `symphony -t xxxx cron run` where `xxxx` is a author login token. E.g. this will run the console tasks command every 1 minute and save the output to `/logs/symphony-cron.log`

    */1 * * * * SYMPHONY_DOCROOT=/path/to/symphony /absolute/path/to/console/bin/symphony -- -t e1f8781e cron run >> /logs/symphony-cron.log 2>&1

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

## Running Tasks

This extension provides a new command called 'run'. It can be executed with the Console extension on the command-line like so (this assumes you have correctly installed the [Console Extension for Symphony CMS](https://github.com/pointybeard/console) and followed the 'Optional Setup' steps):

    symphony --user=USER cron run

You must authenticate to run the command, so use either `--user` or `--token`.

The output from the `run` command will look something like this:

Running Tasks (2 task/s found)
(1/2): Another Test  ... done (time: 2.03 seconds, memory: 4.00 mb)
(2/2): Test Task  ... done (time: 2.05 seconds, memory: 4.00 mb)

The `run` command has 2 additional options you can use: `--force` and `--task=NAME`.

`--force` will, unsurprisingly, force all tasks to be run regardless of their next execution time or enabled status. `--task=NAME` allows you to run a specific task by name specifying its name. Note that both `--force` and `--task` can be used at the same time. e.g.

    symphony --token=12345 cron run --force --task=mytask.task

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/cron/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/cron/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Cron Tasks Extension for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
