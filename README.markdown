# Symphony Cron

- Version: 1.0.2
- Author: Alistair Kearney (hi@alistairkearney.com)
- Release Date: 20th May 2016
- Requirements: Symphony 2.6.x, PHP 5.4.* or greater, Shell Extension 1.0.2 (https://github.com/pointybeard/shell)

A system for automating tasks via the Symphony shell extension and cron.

## Installation and Setup

1.	Ensure that the [Symphony Shell extension](https://github.com/pointybeard/shell) has been installed first.

2.	Upload the 'cron' folder to your Symphony 'extensions' folder.

3.	Create a folder called 'cron' inside `/manifest`. Ensure that it is writable by PHP

2.	Enable it by selecting "Cron", choose Enable from the `with-selected` menu, then click Apply.

3. (Optional) Create a crontab entry (crontab -e) that executes the command "symphony -t XXX -e cron -c runTasks"


##Usage

Use the 'Cron' interface in the Admin, found under the 'System' menu to create and edit tasks. Newly created tasks are stored in `/manifest/cron`, making them suitable for version control.

Anytime the `runTasks` command is executed via the command line, all pending tasks (those due for execution) will be run. This process can be automated with an entry in your server's `crontab`.

It is also possible to create tasks manually.

## Creating a task manually

1. Create a file with a unique name and extenion of `.task` (e.g. `???.task`) and place it in `/manifest/cron`

2. The contents must be XML and is formatted as follows:

```xml
	<task>
		<command>ls -lah</command>
		<name>I am a nice name</name>
		<description>I am an optional description</description>
		<interval type="minute|hour|day|week">45</interval>
		<start>2009-08-21 07:45am</start>
		<finish>2009-09-13 1215</finish>
	</task>
```
3. Browse to `System > Cron` in the Symphony Administration

4. Enable the newly created Cron task
