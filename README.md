# Laravel GRPC Server Example

## Install

```bash
$ composer install
$ cp .env.example .env
$ php artisan key:generate
$ php artisan octane:install --server=roadrunner # make binary 
$ cp .rr.yaml.example .rr.yaml
```

---

## Generate proto

Download the current version for your system of the binary file protoc-gen-php-grpc for generating grpc php, 

and replace it in the ./bin folder - https://github.com/roadrunner-server/roadrunner/releases 

```bash
$ protoc -I=./proto --php_out=./gen --php-grpc_out=./gen --plugin=protoc-gen-php-grpc=./bin/protoc-gen-php-grpc service.proto

# Specify the appropriate namespace in the protocol file in the folder with the generated files.
# Example proto.service
```

---

## Server run

```bash
$ php worker.php
$ ./rr serve
```
