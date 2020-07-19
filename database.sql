create database if not exists api_rest_symfony;

USE api_rest_symfony;

Create TABLE users(
id int auto_increment not null,
name varchar(255) not null,
surname  varchar(255) ,
email  varchar(255) not null,
password varchar(255) not null,
role varchar(20),
created_at  datetime DEFAULT current_timestamp,
updated_at datetime DEFAULT current_timestamp,
CONSTRAINT pk_users PRIMARY KEY(id) 

)ENGINE=InnoDb;

Create TABLE videos(
id int auto_increment not null,
user_id int not null,
title varchar(255) not null,
description text,
url varchar(255) not null,
status varchar(60),
created_at datetime DEFAULT current_timestamp ,
updated_at datetime DEFAULT current_timestamp,
CONSTRAINT pk_videos PRIMARY KEY(id),
CONSTRAINT fk_video_user FOREIGN KEY(user_id) REFERENCES users(id)

)ENGINE=InnoDb;