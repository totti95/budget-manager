<?php
/**
 * phpMyAdmin configuration override
 * Cookie-based authentication for development security
 */

// Cookie validity: 24 hours
$cfg['LoginCookieValidity'] = 86400;

// Use cookie authentication (requires login)
$cfg['Servers'][1]['auth_type'] = 'cookie';
