#!/bin/bash

git add adminModule/users.php
git commit -m "Update users management page (AM 27937)"

git add adminModule/manage_submissions.php
git commit -m "Update manage submissions page (AM 27937)"

git add adminModule/reports.php
git commit -m "Update reports page (AM 27937)"

git add adminModule/settings.php
git commit -m "Update settings page (AM 27937)"

git add index.php
git commit -m "Update index page (AM 27937)"

git add assets/css/style.css
git commit -m "Update stylesheet (AM 27937)"

git add database/schema.sql
git commit -m "Update database schema (AM 27937)"

git add README.md
git commit -m "Update README (AM 27937)"

git push
