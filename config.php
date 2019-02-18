<?php

/*
 * There are two types of cache storage
 * - database - store cache inside a SQLiTE database
 * - file     - store cache inside a file system
 *
 * depending on the server, some might disable SQLiTE module
 * while file system works everywhere.
 *
 * By default this application uses file system
 *
 * $config['CACHE_TYPE'] := 'file'|'database'
 */
define('CACHE_TYPE', 'file');

/*
 * In case that you chose to use SQLiTE database,
 * then you might need to choose a custom name for
 * your database.
 *
 * Well, the default name is cache.db
 *
 * $config['CACHE_DBFILENAME'] := dbname
 */
define('CACHE_DBFILENAME', 'cache.db');

/*
 * This option set up how long we can to cache an old 
 * data when needing to fetch it again.
 *
 * This factor determine how accurate your cache compared
 * to the one from official source of data. The longer the cache
 * time, the data might be outdated. The newest one might consume
 * more server bandwidth as the application will fetch the data
 * more often than usual
 *
 * $config['CACHE_TIMELEFT'] := hour;
 */
define('CACHE_TIMELEFT', 1);

/*
 * You may not need to change this value, unless somehow
 * they changed icress url. 
 */
define('ICRESS_URL', 'icress.uitm.edu.my');

?>
