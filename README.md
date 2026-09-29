# Pirsch WordPress

The official Wordpress Plugin for Pirsch Analytics.

## Usage and Changelog

Please refer to the [readme.txt](readme.txt).

## Testing the Plugin

If you run into any problems, like the page views not being counted, please check out the [pirsch-check.php](/tools/pirsch-check.php) script. It helps with debugging and prints useful information, like the request parameters and plugin configuration.

## Local Development

Download and install WordPress locally (run `php -S localhost:8080` inside the WordPress root directory). Change into `wp-content/plugins/` and check out this Git repository or link to it (`ln -s /path/to/repo ./pirsch-wordpress`). The plugin needs to be enabled on the plugin settings page.

## SVN

```
# 1. Check out the repo (first time only)
svn co https://plugins.svn.wordpress.org/pirsch-analytics
cd pirsch-analytics

# 2. Make release and copy the directory to tags/
VERSION=<version> make release

# 3. Check status and add files
svn status
svn add tags/<version>

# 4. Commit and enter password
svn ci -m "<commit message>" --username m5blum
```

## License

MIT
