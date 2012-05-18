# Symphony Cron

A system for automating tasks via cron.

Please be aware that this extension is in its infancy. There might be problems, limitations or major bugs. It is also necessary to have the latest integration branch code. At time of writing, the latest stable release was 2.0.6.


## Installation
1. Upload the 'cron' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Cron", choose Enable from the with-selected menu, then click Apply.
3. Create a crontab entry (crontab -e) that executes the command "symphony -t XXX cron run-tasks"


## Usage

Use the 'Cron' interface in the Admin, found under the 'System' menu. Newly created jobs are stored in /manifest/cron, making them suitable for version control. 

Creating tasks manually

- Create a file with a unique name and place it in /manifest/cron
- The XML format is as follows:

			<task>
				<command>ls -lah</command>
				<name>I am a nice name</name>
				<description>I am an optional description</description>
				<interval type="minute|hour|day|week">45</interval>
				<start>2009-08-21 07:45am</start>
				<finish>2009-09-13 1215</finish>
			</task>			
		
- Browse to System > Cron in the Symphony Administration
- Enable the newly created Cron task

## Todo
- Have way to trigger tasks without using cron (Poor mans cron)