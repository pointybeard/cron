# Cron Tasks Extension for Symphony CMS

-   Version: 2.1.0
-   Date: April 17 2020
-   [Release notes][1]
-   [GitHub repository][2]

An extension for automating tasks via the Symphony CMS Console extension

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, run `composer update` to install required packages and then enable it though the interface.

### Requirements

This extension requires PHP 7.3 or greater. For use with earlier version of PHP, please use v1.1.1 of this extension instead (`git clone -b1.1.1 https://github.com/pointybeard/cron.git`).

The [Console Extension for Symphony CMS][6] must also be installed.

This extension depends on the following Composer libraries:

-   [Symphony CMS: Extended Base Class Library][14]
-   [SymphonyCMS PDO Connector][7]
-   [Property Bag][9]
-   [PHP Helpers][8]
-   [phpunit/php-timer][10]

Run `composer update` on the `extension/cron` directory to install these.

## Usage

Use the 'Cron' interface in the Admin, found under the 'System' menu, to create and edit tasks. Newly created tasks are stored in `/manifest/cron`, making them suitable for version control.

Anytime the `tasks` command is executed via the command-line, all pending tasks (those due for execution) will be run. This process can be automated with an entry in your server's `crontab` (see, [Optional Setup][#optional-setup] below).

## Creating a task manually

1. Create a file with a unique name and extension of `.task` (e.g. `mytask.task`) and place it in `/manifest/cron`

2. The contents must be valid JSON:

```json
{
    "name": "NAME OF TASK",
    "command": "COMMAND TO RUN",
    "description": "Optional Description",
    "interval": {
        "type": "<second | minute | hour | day | week>",
        "duration": xx
    },
    "start": "2009-08-21 07:45am",
    "finish": null
}
```

-   **Name**: The name of the task. This can be anything
-   **Command**: The console command to run. All commands will be run on Bash
-   **Description**: This is optional
-   **Interval**: This is the amount of time that must elapse before running the task again
-   **Type**: Must be one of `second`, `minute`, `hour`, `day`, `week`
-   **Value**: Must be a number


3. Browse to `System > Cron` in the Symphony Administration

4. Enable the newly created Cron task

## Running Tasks

This extension provides a new command called 'run'. It can be executed with the Console extension on the command-line like so (this assumes you have correctly installed the [Console Extension for Symphony CMS][6] and followed the 'Optional Setup' steps):

    symphony --user=USER cron run

You must authenticate to run the command, so use either `--user` or `--token`.

The output from the `run` command will look something like this:

    Running Tasks (2 task/s found)
    (1/2): Another Test  ... done (time: 2.03 seconds, memory: 4.00 mb)
    (2/2): Test Task  ... done (time: 2.05 seconds, memory: 4.00 mb)

The `run` command has 2 additional options you can use: `--force` and `--task=NAME`.

`--force` will, unsurprisingly, force all tasks to be run regardless of their next execution time or enabled status. `--task=NAME` allows you to run a specific task by name specifying its name. Note that both `--force` and `--task` can be used at the same time. e.g.

    symphony --token=12345 cron run --force --task=mytask.task

### Automating Running of Tasks

#### Triggered

Attach the event "Cron: Trigger Tasks" to your pages via the Symphony Pages interface and any time that page is requested all pending tasks will be run silently in the background. Some XML will be added to your page signifying that the event triggered and which tasks were run. It will not interrupt the page loading, even if there was an error.

**Note: It is not recommended to use this method for time intensive tasks or tasks that must be run on a strict schedule.**

#### Crontab

This is the preferred method for triggering tasks and is availble on all *nix systems. To do this, create a crontab entry (`sudo crontab -e`) that executes the command `symphony -t xxxx cron run` where `xxxx` is a author login token.

E.g. this will run the console tasks command every 1 minute and save the output to `/logs/symphony-cron.log`:

    */1 * * * * SYMPHONY_DOCROOT=/path/to/symphony /path/to/console/bin/symphony -- -t xxxx cron run >> /logs/symphony-cron.log 2>&1

#### frequent-cron

[frequent-cron][11] is a linux daemon that allows commands to be run more frequently than 1 minute (a limitation of crontab). Similar to crontab, frequent-cron triggers at a set interval (unless the previous task is still running, in which case it will wait). Use the following steps to set up your cron tasks to run via frequent-cron.

1. Clone [https://github.com/homer6/frequent-cron.git][12] and follow the install instructions.
2. Create a new file called `symphony_cron_runner.sh` with the following contents:

    #!/usr/bin/env bash
    SYMPHONY_DOCROOT=/path/to/symphony /path/to/console/bin/symphony -- -t xxxx cron run >> /logs/symphony-frequent-cron.log 2>&1

3. Modify `COMMAND` in `init_script.tpl` with the path to `symphony_cron_runner.sh`
4. Follow the steps under "[Starting the Service (Using init.d)][13]" in the frequent-cron README

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker][3],
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation][4] for guidelines about how to get involved.

## License

"Cron Tasks Extension for Symphony CMS" is released under the [MIT License][5].

[1]: https://github.com/pointybeard/cron/blob/master/CHANGELOG.md
[2]: https://github.com/pointybeard/cron
[3]: https://github.com/pointybeard/cron/issues
[4]: https://github.com/pointybeard/cron/blob/master/CONTRIBUTING.md
[5]: http://www.opensource.org/licenses/MIT
[6]: https://github.com/pointybeard/console
[7]: https://github.com/pointybeard/symphony-pdo
[8]: https://github.com/pointybeard/helpers
[9]: https://github.com/pointybeard/property-bag
[10]: https://github.com/sebastianbergmann/php-timer
[11]: https://github.com/homer6/frequent-cron
[12]: https://github.com/homer6/frequent-cron.git
[13]: https://github.com/homer6/frequent-cron#user-content-starting-the-service-using-initd
[14]: https://github.com/pointybeard/symphony-extended
