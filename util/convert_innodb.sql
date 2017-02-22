#  script to convert tables from MyISAM to InnoDB
#  change the %PLACEHOLDER% to the actual name of your Friendica DB
 
SELECT CONCAT('ALTER TABLE ',table_schema,'.',table_name,' engine=InnoDB;') 
FROM information_schema.tables 
WHERE engine = 'MyISAM' AND  `table_schema` = '%PLACEHOLDER%';
