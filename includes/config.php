<?php
// Database settings (change these to match your MySQL setup)
const DB_HOST = 'localhost';
const DB_NAME = 'samfix';
const DB_USER = 'root';
const DB_PASS = 'abregana';   // empty '' only if your MySQL has no password

// App settings
const APP_NAME = 'SamFix';
const BASE_URL = '/SamFix';  // must match how you open the site (see below)

const STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

// Shop details shown on the home page (edit these to your real shop info)
const SHOP_NAME    = 'SamFix Service Center';
const SHOP_ADDRESS = 'Your street address, City, Province';
const SHOP_HOURS   = ['Monday to Friday · 9 AM to 6 PM', 'Saturday · 10 AM to 4 PM'];
const SHOP_PHONE   = '0900 000 0000';
const SHOP_EMAIL   = 'hello@samfix.example';