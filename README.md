# Health Product Recommender Lite

Health Product Recommender Lite is a lightweight, responsive WordPress plugin that generates product recommendations based on a short health questionnaire. The plugin is compatible with the Woodmart theme and Elementor and stores quiz results in its own table for later export. From version 1.3.5 the Excel export works even if the server does not have the PHP ZipArchive extension – in that case an `.xls` file is generated automatically.

## Installation

1. Download the `health-product-recommender-lite.zip` file from the [GitHub releases page](https://github.com/beopop/eliksir/releases).
2. In your WordPress dashboard navigate to **Plugins → Add New → Upload Plugin** and upload the downloaded ZIP file.
3. Activate **Health Product Recommender Lite**.

## Configuring GitHub updates

The plugin can update itself directly from the `beopop/eliksir` repository. If you are using a private repository or hit GitHub API rate limits you can supply a personal access token.

1. Open **Health Quiz** in the WordPress admin menu and enter your token in the **GitHub token** field.
2. Alternatively define the constant `HPRL_GITHUB_TOKEN` in `wp-config.php`:

```php
define( 'HPRL_GITHUB_TOKEN', 'your_token_here' );
```

## Troubleshooting updates

If the update process fails:

- Verify that the token is correct and has permission to access the repository.
- Enable **Debug log** in the plugin settings to display any update errors.
- Manual installation of a release ZIP is always possible if automatic updates do not work.

## Version history

For a list of changes please see the [changelog in `readme.txt`](health-product-recommender-lite/readme.txt).
