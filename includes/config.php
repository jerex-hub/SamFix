<?php
// Database settings (change these to match your MySQL setup)
const DB_HOST = 'localhost';
const DB_NAME = 'samfix';
const DB_USER = 'root';
const DB_PASS = 'abregana';   // usba lang ni if unsa pass sa inyo db

// App settings
const APP_NAME = 'SamFix';
const BASE_URL = '/SamFix';  // must match how you open the site

const STATUSES = ['Pending', 'In Progress', 'Ready for pickup', 'Completed', 'Cancelled'];

// Shop details shown on the home page (edit these to your real shop info)
const SHOP_NAME       = 'SamFix Service Center';
const SHOP_ADDRESS    = 'Poblacion, Claveria, Misamis Oriental, Philippines';
const SHOP_AREA       = 'Poblacion, Claveria, Misamis Oriental, Philippines'; 
const SHOP_HOURS      = ['Monday to Friday · 9 AM to 6 PM', 'Saturday · 10 AM to 4 PM'];
const SHOP_PHONE      = '09682215995';
const SHOP_EMAIL      = 'samfix@gmail.com';
const SHOP_TURNAROUND = '2–3 business days';