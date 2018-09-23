# Symphony Cron

- Version: v1.1.0
- Date: Sept 23 2018
- Requirements: Symphony 2.6.x, PHP 5.6 or greater, Shell Extension 1.0.2 (https://github.com/pointybeard/shell)
- [Release notes](https://github.com/pointybeard/cron/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/cron)

A system for automating tasks via the Symphony shell extension and cron.

## Installation and Setup

1.	Ensure that the [Symphony Shell extension](https://github.com/pointybeard/shell) has been installed first.

2.	Run `composer update` from within the Cron extension folder to install required packages.

3.	Upload the 'cron' folder to your Symphony 'extensions' folder.

4.	Create a folder called 'cron' inside `/manifest`. Ensure that it is writable by PHP

5.	Enable it by selecting "Cron", choose Enable from the `with-selected` menu, then click Apply.

6. (Optional) Create a crontab entry (crontab -e) that executes the command "symphony -t XXX -e cron -c runTasks"


##Usage

Use the 'Cron' interface in the Admin, found under the 'System' menu to create and edit tasks. Newly created tasks are stored in `/manifest/cron`, making them suitable for version control.

Anytime the `runTasks` command is executed via the command line, all pending tasks (those due for execution) will be run. This process can be automated with an entry in your server's `crontab`.

It is also possible to create tasks manually.

## Creating a task manually

1. Create a file with a unique name and extension of `.task` (e.g. `???.task`) and place it in `/manifest/cron`

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

"Cron" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
