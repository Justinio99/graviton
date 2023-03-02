# Graviton 

[![CI](https://github.com/libgraviton/graviton/actions/workflows/ci.yml/badge.svg)](https://github.com/libgraviton/graviton/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/libgraviton/graviton/badge.svg?branch=develop)](https://coveralls.io/github/libgraviton/graviton?branch=develop)
![Packagist Version](https://img.shields.io/packagist/v/graviton/graviton)
![Packagist Downloads](https://img.shields.io/packagist/dt/graviton/graviton)
![Packagist License](https://img.shields.io/packagist/l/graviton/graviton)

Graviton is a Symfony and Doctrine Mongo ODM based REST server generation toolkit. So it stores all data in MongoDB.

You can define your REST service in an simple JSON format, run the generator - and voilà - your REST API is ready to go.

Let's say you define this file:

```json
{
  "id": "Example",
  "service": {
    "readOnly": false,
    "routerBase": "/example/endpoint/"
  },
  "target": {
    "fields": [
      {
        "name": "id",
        "type": "string"
      },
      {
        "name": "data",
        "type": "string"
      }
    ]
  }
}

``` 

Then you run the generator

```bash
php bin/console graviton:generate:dynamicbundles
```

And once running, you will have a full RESTful endpoint at `/example/endpoint`, supporting GET, POST, PUT, DELETE and PATCH as well as a valid
generated JSON schema endpoint, pagination headers (`Link` as github does it) and much more.

The generated code are static PHP files and configuration for the Serializer and Symfony and is regarded as _disposable_. You can always
regenerate it - don't touch the generated code.

The application is highly optimized for runtime performance, particurarly in the context of PHP-FPM with activated opcache.

It boasts many additional features (such as special validators and many flags and configurations) which are currently mostly undocumented as this project was not built for public usage in mind. But if
there is interest and support from outside users, we welcome questions and contributions.

## Install

```bash
composer install
```

## Usage

```bash
./dev-cleanstart.sh
```

and

```bash
php bin/console
```

## Documentation

There are some general docs on interacting with the codebase as a whole. 

- [Development](app/Resources/doc/DEVELOPMENT.md)
- [Deploy](app/Resources/doc/DEPLOY.md)

Some even broader scoped docs in a seperate repo.

- [docs.graviton.scbs.ch](https://docs.graviton.scbs.ch/)

The bundle readme files which show how to interact with
the various subsystems.

- [DocumentBundle](src/Graviton/DocumentBundle/README.md)
- [FileBundle](src/Graviton/FileBundle/README.md)
- [GeneratorBundle](src/Graviton/GeneratorBundle/README.md)
- [I18nBundle](src/Graviton/I18nBundle/README.md)
- [SecurityBundle](src/Graviton/SecurityBundle/README.md)
- [TestBundle](src/Graviton/TestBundle/README.md)
- [AnalyticsBundle](src/Graviton/AnalyticsBundle/README.md)

And not to forget, the all important [CHANGELOG](https://github.com/libgraviton/graviton/releases).

### Tracing

This component comes with a tracing bundle, but it is deactivated by default. To enable it, one must set this ENVs:

```
TRACING_ENABLED=true
```

This enables the bundle. Then you need to set the `jaeger-bundle` specific envs:

```
AUXMONEY_OPENTRACING_AGENT_HOST=zipkin-hostname # hostname to jaeger
AUXMONEY_OPENTRACING_AGENT_PORT=6831 # port to jaeger
AUXMONEY_OPENTRACING_SAMPLER_VALUE=true # const sampler value (sends always if true or never if false)
```

See [this](https://packagist.org/packages/auxmoney/opentracing-bundle-core) and [this](https://packagist.org/packages/auxmoney/opentracing-bundle-jaeger) page about the 2 bundles.
