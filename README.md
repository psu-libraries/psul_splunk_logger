## INTRODUCTION

The PSUL Splunk Logger module is add push messages to stdout so they can be
consumed by splunk.

This is based on the [log_stdout](https://www.drupal.org/project/log_stdout)
but that has issueswhen failing over memcache because it uses configs.

The logs can be found at https://search.splunk.psu.edu/en-US/app/search/search.  Search by `app=drupal` to
filter just by drupal logs.
