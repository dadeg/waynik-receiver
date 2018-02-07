Setting up this repo
=========
Install Git on your machine
https://git-scm.com/book/en/v2/Getting-Started-Installing-Git

Download these files
```git clone https://[YOUR_BITBUCKET_USERNAME]@bitbucket.org/dadeg/waynik-monitor.git```


Setting up the website
=========
Install composer
http://getcomposer.org

Install dependencies through composer
```composer install```

DOCKER
=========
none of this is needed since docker-compose does it for us. but this was what I learned while I was trialling and erroring
docker build .

for dev: for some reason, binding straight to /data causes an nginx error in `docker logs [container_id]`
docker run --name waynik_user_admin -p 20030:80 -d -v /home/core/projects/waynik/user-admin:/data/www [image_id]

for prod? check this.
docker run --name waynik_user_admin -p 80:80 -d [image_id]

docker stop nginx && docker rm nginx
docker exec -it [container_id] bash

delete unused images to clean up docker
docker rmi -f $(docker images | grep "<none>" | awk "{print \$3}")

If there is an issue with the container working properly when you run bash into it and curl localhost, but it doesnt show the same info on the host, delete images and rebuild.

DOCKER COMPOSE
=========
this docker-compose file is linked to the user-admin mysql instance so it must be running too.
start the server:
`docker-compose up -d receiver`
stop the server:
`docker-compose stop`
build the database, should only need to do this once or when you want a refresh:
`docker-compose run receiver bash /data/www/db/build.sh`
make a backup of the database, mysql container must have a volume set up to host:
`docker-compose run mysql bash -c 'exec mysqldump --all-databases -h mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" > /data/all-databases.sql'`
restore dump to the database (untested):
`docker exec -i imageNameOrId mysql nameOfYourDb < ~/path/to/your/dump.sql`

Deploying on AWS EC2 Container Service
=========
This was tricky, I am not sure whether I have all the settings right.
In order to build the database, I think I have to SSH into the EC2 instance and run the commands in plain docker, not docker-compose like above.

To build database:
`docker exec [container_name_of_website] bash /data/www/db/build.sh`

Make a backup of the database (note: directory and file must already exist. mkdir /waynik/data/backup and touch all-databases.sql while ssh'd into ec2 instance):
`docker exec [container_name_of_website] bash -c 'exec mysqldump --all-databases -h mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" > /waynik/data/backup/all-databases.sql'`

So far, I