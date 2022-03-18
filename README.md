# music-dl

[简体中文](README-zh_CN.md) | [ENGLISH](README.md)

> Command Line Music Search and Downloader. - 命令行音乐搜索和下载器。

[![tests](https://github.com/guanguans/music-dl/workflows/tests/badge.svg)](https://github.com/guanguans/music-dl/actions)
[![check & fix styling](https://github.com/guanguans/music-dl/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/guanguans/music-dl/actions)
[![Latest Stable Version](https://poser.pugx.org/guanguans/music-dl/v)](//packagist.org/packages/guanguans/music-dl)
[![Total Downloads](https://poser.pugx.org/guanguans/music-dl/downloads)](//packagist.org/packages/guanguans/music-dl)
[![License](https://poser.pugx.org/guanguans/music-dl/license)](//packagist.org/packages/guanguans/music-dl)
![GitHub repo size](https://img.shields.io/github/repo-size/guanguans/music-dl)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/guanguans/music-dl)

![usage](resources/usage.gif)

## Requirement

* PHP >= 8.0

## Installation

### File download and install

在 [releases](https://github.com/guanguans/music-dl/releases) 页面， 下载对应版本 `music-dl` 文件。

### Globally Install

```shell
$ composer global require guanguans/music-dl --dev
```

### Current directory installation

```shell
$ composer create-project guanguans/music-dl --no-dev
```

## Usage

You can just execute the tool on your cli.

```bash
$ music-dl
```

```text
╰─ ./music-dl                                                                       ─╯

     __  __           _        _____  _      
    |  \/  |         (_)      |  __ \| |     
    | \  / |_   _ ___ _  ___  | |  | | |     
    | |\/| | | | / __| |/ __| | |  | | |     
    | |  | | |_| \__ \ | (__  | |__| | |____ 
    |_|  |_|\__,_|___/_|\___| |_____/|______|                                       

 请输入要关键字如：一个短篇  腰乐队  Yesterday once more，或 Ctrl+C 退出 [腰乐队]:
 > |

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

* [guanguans](https://github.com/guanguans)
* [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
