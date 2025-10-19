<?php
/**
 * phpMyAdmin configuration override
 * Auto-login as root user
 */

$cfg['LoginCookieValidity'] = 86400;
$cfg['Servers'][1]['auth_type'] = 'config';
$cfg['Servers'][1]['user'] = 'root';
$cfg['Servers'][1]['password'] = 'root_pass';
$cfg['Servers'][1]['AllowNoPassword'] = true;
