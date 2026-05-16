# Monitoring

## Endpoints

Currently, there are two endpoints for statistics available

-	`/stats` Returns some basic statistics of the current node
-	`/stats/caching` Returns statistics of cache or lock instances, which are used for the currend node

### `/stats`

The statistics contain data about the worker performance, the last cron call, number of reports, inbound and outbound packets, posts and comments.

### `/stats/caching`

The statistics contain data about the opcache, the used caching (like memory usage, hits/misses, entries, ...) and the used lock (including the cache data)

## Configuration

Please define 'stats_key' in your local.config.php in the 'system' section to be able to access the statistics page at /stats?key=your-defined-stats_key

## 3rd Party monitoring tools

### Zabbix

To monitor the health status of your Friendica installation, you can use for example a tool like Zabbix.

### Prometheus

To use [prometheus](https://prometheus.io) for gathering metrics, use the [Friendica exporter](https://git.friendi.ca/friendica/friendica-exporter).

You can find the installation instructions here: https://git.friendi.ca/friendica/friendica-exporter#installation
