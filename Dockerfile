FROM php:alpine
MAINTAINER tungshooter@gmail.com 

COPY report.php /scripts/report.php

ENTRYPOINT ["php", "/scripts/report.php"]
