FROM php:alpine

MAINTAINER tungshooter@gmail.com

COPY report.php /scripts/report.php
COPY resolv.conf /etc/resolv.conf
