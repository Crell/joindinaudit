Audit tool for Joind.in
=======================

This is a simple and straightforward tool for scraping data out of Joind.in 
and running some basic analysis on it.  For more background, see my blog post:

http://www.garfieldtech.com/blog/php-conference-data

h2. Setup
-----

Clone down the repository, then run `composer install` to grab a few dependencies.

You will need to create a local MySQL database, and set up an Environment variable
for its database credentials. The database user will need full access to that
database.

````
$ export joindin_db=mysql://user:pass@localhost/yourdbname
````

h2. Usage
-----

There are four scripts to run, in the repository root:

h3. `init.php`

Run this once to create the database tables in the database defined above. You
will need to run it once before each time you run `download.php`, as that script
needs an empty database.

h3. `download.php`

Run this to download the full available dataset from Joind.in.  It will provide
some basic output along the way.  On my system it takes about 4 minutes, but 
your download speed may vary.

h3. `derive.php`

This script generates some derived data that is slow to calculate, and stores
it in the database.  It generates no output when everything works properly.

h3. `report.php`

This script actually produces the report output. It produces a very simple HTML
page called report.html, in the project root.  That's where all the useful 
data can be seen. :-)

h2. Contributing
-----------------

Want to add another report?  PRs welcome. :-)

Keep using the same format and tool chain in `report()` as the existing code, for
consistency.  If you need to add more derived tables or columns, modify `derive.php`
as needed, again keeping the same structure and pattern.

h2. License
------------

This tool is copyright 2015 Larry Garfield and released under the MIT license.

Vendored packages are under their own respective licenses.