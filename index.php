<?php

/**
 * Laravel cPanel Root Front Controller
 * Allows deploying the project directly into cPanel's public_html or subdomain directory
 * while keeping public/index.php intact for standard DocumentRoot=/public setups.
 */

require __DIR__.'/public/index.php';
