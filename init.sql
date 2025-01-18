ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'rocola8792';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';
CREATE USER 'rocola'@'%' IDENTIFIED WITH mysql_native_password BY 'rocola8792';
GRANT ALL PRIVILEGES ON *.* TO 'rocola'@'%';
FLUSH PRIVILEGES;