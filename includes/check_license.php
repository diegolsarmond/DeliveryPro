<?php
// File Hash: a62893c09ad92af74ec21f467335cc55


function validarCodigoLicenca($_41ef8940) {
    if (!preg_match('/^\d{6}-[A-Z0-9]{8}-[a-f0-9]{4}$/', $_41ef8940)) {
        return (bool)0;
    }
    $_2a2f26da = explode('-', $_41ef8940);
    $_8d777f38 = $_2a2f26da[0];
    $_c08cbbfd = $_2a2f26da[1];
    $_226190d9 = $_2a2f26da[2];
    $_96339548 = 'deliverypro2024#';
    $_593616de = $_8d777f38 . $_c08cbbfd . $_96339548;
    $_d1949086 = substr(hash('sha256', $_593616de), 0, 4);
    if ($_226190d9 !== $_d1949086) {
        return (bool)0;
    }
    $_1a2ed467 = \DateTime::createFromFormat('ymd', $_8d777f38);
    if (!$_1a2ed467) {
        return (bool)0;
    }
    $_da53ac20 = new \DateTime();
    if ($_da53ac20 < $_1a2ed467) {
        return (bool)0;
    }
    $_7e7a587b = $_da53ac20->diff($_1a2ed467)->days;
    return $_7e7a587b <= 30;
}
    try {
        $_217ecb18 = $_0c1d0e2e->prepare("
            SELECT * FROM license_codes 
            WHERE is_active = 1
            ORDER BY id DESC 
            LIMIT 1
        ");
        if (!$_217ecb18->execute()) {
            error_log("\x45\x72\x72\x6f\x20\x61\x6f\x20\x65\x78\x65\x63\x75\x74\x61\x72\x20\x71\x75\x65\x72\x79\x3a\x20" . $_0c1d0e2e->error);
            return (bool)0;
        }
        $_e9f5feef = $_217ecb18->get_result()->fetch_assoc();
        if (!$_e9f5feef) {
            error_log("\x4e\x65\x6e\x68\x75\x6d\x61\x20\x6c\x69\x63\x65\x6e\xc3\xa7\x61\x20\x61\x74\x69\x76\x61\x20\x65\x6e\x63\x6f\x6e\x74\x72\x61\x64\x61");
            return (bool)0;
        }
        if (!preg_match('/^(\d{6})-/', $_e9f5feef['code'], $_9c28d32d)) {
            error_log("\x46\x6f\x72\x6d\x61\x74\x6f\x20\x64\x65\x20\x63\xc3\xb3\x64\x69\x67\x6f\x20\x69\x6e\x76\xc3\xa1\x6c\x69\x64\x6f");
            return (bool)0;
        }
        $_a3f0ef9d = $_9c28d32d[1];
        $_a94879f4 = \DateTime::createFromFormat('ymd', $_a3f0ef9d);
        if (!$_a94879f4) {
            error_log("\x44\x61\x74\x61\x20\x64\x6f\x20\x63\xc3\xb3\x64\x69\x67\x6f\x20\x69\x6e\x76\xc3\xa1\x6c\x69\x64\x61");
            return (bool)0;
        }
        if (!validarCodigoLicenca($_e9f5feef['code'])) {
            error_log("\x43\xc3\xb3\x64\x69\x67\x6f\x20\x64\x65\x20\x6c\x69\x63\x65\x6e\xc3\xa7\x61\x20\x69\x6e\x76\xc3\xa1\x6c\x69\x64\x6f");
            $_217ecb18 = $_0c1d0e2e->prepare("UPDATE license_codes SET is_active = 0 WHERE id = ?");
            $_217ecb18->bind_param("\x69", $_e9f5feef['id']);
            $_217ecb18->execute();
            return (bool)0;
        }
        $_a7f62bb2 = new DateTime($_e9f5feef['valid_until']);
        $_0f5491c9 = clone $_a94879f4;
        $_0f5491c9->modify('+30 days');
        if ($_a7f62bb2->format('Y-m-d') !== $_0f5491c9->format('Y-m-d')) {
            error_log("\x44\x61\x74\x61\x20\x64\x65\x20\x76\x61\x6c\x69\x64\x61\x64\x65\x20\x6d\x61\x6e\x69\x70\x75\x6c\x61\x64\x61");
            $_217ecb18 = $_0c1d0e2e->prepare("UPDATE license_codes SET is_active = 0 WHERE id = ?");
            $_217ecb18->bind_param("\x69", $_e9f5feef['id']);
            $_217ecb18->execute();
            return (bool)0;
        }
        $_da53ac20 = new DateTime();
        if ($_da53ac20 > $_a7f62bb2) {
            error_log("\x4c\x69\x63\x65\x6e\xc3\xa7\x61\x20\x65\x78\x70\x69\x72\x61\x64\x61");
            $_217ecb18 = $_0c1d0e2e->prepare("UPDATE license_codes SET is_active = 0 WHERE id = ?");
            $_217ecb18->bind_param("\x69", $_e9f5feef['id']);
            $_217ecb18->execute();
            return (bool)0;
        }
        $_217ecb18 = $_0c1d0e2e->prepare("UPDATE license_codes SET ultima_verificacao = NOW() WHERE id = ?");
        $_217ecb18->bind_param("\x69", $_e9f5feef['id']);
        $_217ecb18->execute();
        return (bool)1;
    } catch (Exception $_e1671797) {
        error_log("\x45\x72\x72\x6f\x20\x61\x6f\x20\x76\x65\x72\x69\x66\x69\x63\x61\x72\x20\x6c\x69\x63\x65\x6e\xc3\xa7\x61\x3a\x20" . $_e1671797->getMessage());
        return (bool)0;
    }
} 