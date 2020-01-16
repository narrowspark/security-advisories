<h1 align="center">Narrowspark Security Advisories Database</h1>
<p align="center">
    <a href="https://travis-ci.org/narrowspark/security-advisories"><img src="https://img.shields.io/travis/narrowspark/security-advisories/master.svg?longCache=false&style=flat-square"></a>
    <a href="http://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
</p>

This project is responsible for generating the [PHP Security Advisories Database](https://github.com/FriendsOfPHP/security-advisories) as a JSON file.

## Stability

This package can only be required in its `dev-master` version: there will never be stable/tagged versions because of
the nature of the problem being targeted. Security issues are in fact a moving target, and locking your project to a 
specific tagged version of the package would not make any sense.

This package is therefore only suited for installation in the root of your deployable project.

## Sources

This package extracts information about existing security issues in various composer projects from 
the [FriendsOfPHP/security-advisories](https://github.com/FriendsOfPHP/security-advisories) repository and the [Github security advisories db](https://developer.github.com/v4/object/securityvulnerability/).

> NOTE: Travis cron is configured to run once a day, to check if [PHP Security Advisories Database](https://github.com/FriendsOfPHP/security-advisories) was updated.

> NOTE: The sha in `security-advisories-sha` file is always the last commit sha of [PHP Security Advisories Database](https://github.com/FriendsOfPHP/security-advisories).
