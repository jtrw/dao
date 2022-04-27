# Base image
FROM mysql:8

RUN apt-get -y update

RUN apt-get install -y wget

# Copy starting scripts file
COPY start.sh /usr/src/start.sh
RUN chmod +x /usr/src/start.sh
RUN chown mysql /usr/src/start.sh

# Run necessary services
CMD ["/bin/bash", "/usr/src/start.sh"]