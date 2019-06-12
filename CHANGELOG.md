# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

**View all [Unreleased][] changes here**

## [2.0.0][] - 2019-06-12
#### Changed
-   Major update to refactor code and work with the [Console Extension for Symphony CMS](https://github.com/pointybeard/console) and [PHP Helpers](https://github.com/pointybeard/helpers) meta package
-   Requring PHP7.2 or newer
-   `runTasks` has been renamed to `run`

## [1.1.1][] - 2018-09-23
#### Added
-   Added sorting and a 'duplicate' action in control panel index

#### Changed
-   Ensuring when a task is run that it's force property it set to no

#### Fixed
-   Fixed breaking bug that meant interval duration was always '1'

## [1.1.0] - 2018-09-23
#### Changed
-   Updated content pages to work with changes to Task class
-   Tasks are now saved as JSON instead of XML
-   Task extends `pointybeard/property-bag`
-   Database access is handled by `pointybeard/symphony-pdo` instead of passing a database object around
-   Streamlined saving process

#### Added
-   Requies `pointybeard/symphony-pdo` and `pointybeard/property-bag` packages
-   Two new Exceptions to help give context: `LoadingTaskFailedException` and `WritingTaskFailedException`
-   Added 'force' property. This will trigger the task to run once regardless of it's next execution time
-   Several new constants added to `Task`

## [1.0.3] - 2018-09-22
#### Changed
-   Shell extension compatibility update

## [1.0.2] - 2016-05-20
#### Changed
-   Updated README to include PHP 5.4.* or greater as a requirement (Closes #1)
-   Improved the composer.json and committed the vendor folder as it includes the autoloader (Closes #2)

## [1.0.1] - 2015-07-28
#### Changed
-   Maintenance. Cleaned up Readme and meta. Merged changes from symphonists/cron

## [1.0.0] - 2015-05-26
#### Changed
-   Symphony 2.6.x Compatibility Update

## 0.1.0 - 2009-09-21
#### Added
-   Initial release

[Unreleased]: https://github.com/pointybeard/console/compare/2.0.0...integration
[2.0.0]: https://github.com/pointybeard/console/compare/1.1.1...2.0.0
[1.1.1]: https://github.com/pointybeard/console/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/pointybeard/cron/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/pointybeard/cron/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/pointybeard/cron/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pointybeard/cron/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/pointybeard/cron/compare/0.1.0...1.0.0
