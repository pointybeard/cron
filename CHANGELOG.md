# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.0] - 2018-09-23
#### Changed
- Updated content pages to work with changes to CronTask class
- Tasks are now saved as JSON instead of XML
- CronTask extends `pointybeard/property-bag`
- Database access is handled by `pointybeard/symphony-pdo` instead of passing a database object around
- Streamlined saving process

#### Added
- Requies `pointybeard/symphony-pdo` and `pointybeard/property-bag` packages
- Two new Exceptions to help give context: `LoadingTaskFailedException` and `WritingTaskFailedException`
- Added 'force' property. This will trigger the task to run once regardless of it's next execution time
- Several new constants added to `CronTask`

## [1.0.3] - 2018-09-22
#### Changed
- Shell extension compatibility update

## [1.0.2] - 2016-05-20
#### Changed
- Updated README to include PHP 5.4.* or greater as a requirement (Closes #1)
- Improved the composer.json and committed the vendor folder as it includes the autoloader (Closes #2)

## [1.0.1] - 2015-07-28
#### Changed
- Maintenance. Cleaned up Readme and meta. Merged changes from symphonists/cron

## [1.0.0] - 2015-05-26
#### Changed
- Symphony 2.6.x Compatibility Update

## 0.1.0 - 2009-09-21
#### Added
- Initial release

[1.1.0]: https://github.com/pointybeard/cron/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/pointybeard/cron/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/pointybeard/cron/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pointybeard/cron/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/pointybeard/cron/compare/0.1.0...1.0.0