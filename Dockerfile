FROM php:alpine

MAINTAINER tungshooter@gmail.com

COPY report.php /usr/bin/send_ci_report
COPY resolv.conf /etc/resolv.conf
RUN chmod 755 /usr/bin/send_ci_report
