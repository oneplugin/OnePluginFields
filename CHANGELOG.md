# OnePluginFields Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.0.0 - 2021-10-14
### Added
- Initial release

## 1.0.1 - 2021-10-14
### Changes
- Category tree style updated

## 1.0.2 - 2021-10-14
### Changes
- Fixed an SVG Render issue

## 1.0.3 - 2021-10-18
### Changes
- Fixed an issue where PDF was not rendering when the same field was used more than once on the same page.

## 1.0.4 - 2021-10-20
### Changes
- Added documentation links

## 1.0.5 - 2021-10-20
### Changes
- Minor Javascript changes for updating the documentation links 

## 1.0.6 - 2021-10-20
### Changes
- Fixed an issue where image will use old urls when the volume changes. This happens only in rare situations when an existing site is cloned and the volumes are updated to use new ones.

## 1.0.7 - 2021-10-24
### Changes
- Update to allow for highspeed caching of animated icons by embedding this media asset directly into the page. Enable or disable this from the settings. 
Highly recommend using a CDN like Cloudflare in conjunction with this setting. 

## 1.0.8 - 2021-10-24
### Changes
- Update to allow for highspeed caching of animated icons by embedding this media asset directly into the page. Enable or disable this from the settings. 
Highly recommend using a CDN like Cloudflare in conjunction with this setting. 

## 1.0.9 - 2021-10-24
### Changes
- Update to allow for highspeed caching of animated icons by embedding this media asset directly into the page. Enable or disable this from the settings. 
Highly recommend using a CDN like Cloudflare in conjunction with this setting. 

## 1.0.10 - 2021-12-09
### Fixed
- SVG support is removed from Image Optimization
- New jobs are not created for generating an optimized image if the metadata already exists