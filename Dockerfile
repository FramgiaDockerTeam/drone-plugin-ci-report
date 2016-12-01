FROM php:alpine

MAINTAINER tungshooter@gmail.com

COPY report.php /usr/local/bin/send_ci_report
COPY resolv.conf /etc/resolv.conf
RUN chmod 755 /usr/local/bin/send_ci_report
