# Remote Password

## BETA

This application is still a bit rough around the edges, but is working fine.

## Origin Story

Remote Password is a web application that helps you avoid hardcoding passwords in scripts.

* Suppose you have an RPi that mounts an encrypted external drive for you to use as a local Network Storage.
* You will have to hardcode the password used to unlock the drive and mount it in a script somewhere, that runs on boot. Because I'm definitely not going to be typing it manually every time it restarts for whatever reason.
* But what happens if someone steals your RPi? Do they just boot it up and access your encrypted data?

At this point, revocable passwords would be ideal, however using a third-party secrets management service was too complicated for this task, especially since I wasn't planning on storing API keys etc.

And this is how this RemotePassword was born:

* Create a GPG key pair.
* Encrypt a password using the public key.
* Upload the output to RemotePassword.
* Use the RPass script to fetch, decrypt, and pass the password to whichever command needs it.
* Device compromised? Disable the password remotely and the script will have nothing to fetch. Sure, the private key is still accessible, but since they don't have the encrypted password, it's useless.

For example:

```
mysqldump -u root -p $(rpass "MySQL") > databases.sql
```

## Installation

### Requirements

This application will not run on any PHP version older than 8.1. So v8.1 is the minimum requirement.

```
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1...etc
```

### Setup

#### Clone the repo

It's recommended you clone this application instead of downloading the zip file. This will make life easier when you have to update it.

```
git clone git@github.com:remotepassword/RemotePassword.git
```

#### Setup the environment

Copy `.env.example` to `.env` and update all the required properties such as Application Name, Env, Debug, URL, etc.

Following that, you **must** re-generate the key:

```
php artisan key:generate
```

#### Database

At this point you will need to run the database migrations.

```
php artisan migrate
```

#### Web Server

Setup your web server of preference (nginx/Apache), and visit the site - it will prompt you to create your admin user.

## Using RPass Scripts

You will need one of [rpass-php](https://github.com/sadreck/rpass-php) or [rpass-python](https://github.com/sadreck/rpass-python) scripts in order to easily fetch, validate, and decrypt your passwords.
