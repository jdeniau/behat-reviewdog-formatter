# behat-reviewdog-formatter

A behat formatter that does output to rdjsonl to be handled by reviewdog

## Installation

```sh
composer require --dev jdeniau/behat-reviewdog-formatter
```

## Usage

Configure your `behat.yml` file:

```yaml
default:
  extensions:
    JDeniau\BehatReviewdogFormatter\ReviewdogFormatterExtension: ~

  formatters:
    pretty: true
    reviewdog: # "reviewdog" here is the "name" given in our formatter
      # outputh_path is optional and handled directy by behat
      output_path: 'build/logs/behat'
      # file_name is optional and a custom parameter that we inject into the printer
      file_name: 'reviewdog-behat.json'
      # optional, default to true
      remove_old_file: true
```

### Different output per profile

You can active the extension only for a certain profile by specifying a profile in your command (ex: `--profile=ci`)

For example if you want the pretty formatter by default, but both progress and reviewdog on your CI, you can configure it like that:

```yaml
default:
  extensions:
    JDeniau\BehatReviewdogFormatter\ReviewdogFormatterExtension: ~

  formatters:
    pretty: true

ci:
  formatters:
    pretty: false
    progress: true
    reviewdog:
      output_path: 'build/logs/behat'
      file_name: 'reviewdog-behat.json'
      # optional, default to true
      remove_old_file: true
```

## Related

Want more detail about how you can create your own behat extension ? See how this extension has been made in [this blogpost](https://julien.deniau.me/posts/2024-01-24-custom-behat-formatter)
