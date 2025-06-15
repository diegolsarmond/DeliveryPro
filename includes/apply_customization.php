<?php
$configFile = __DIR__ . '/../customizacao.json';
$config = json_decode(file_get_contents($configFile), true);
?>
<style>
:root {
    --primary-color: <?php echo $config['primary_color']; ?>;
    --primary-hover: <?php echo $config['primary_hover_color']; ?>;
}

.sidebar {
    background: <?php echo $config['navbar_color']; ?> !important;
}
</style>

<script>
window.addEventListener('load', function() {
    // Atualizar logo do menu
    const sidebarLogo = document.querySelector('.sidebar-header img');
    if (sidebarLogo) {
        sidebarLogo.src = '<?php echo $config['logo_menu']; ?>';
    }
    
    // Atualizar texto do rodapé
    const footerText = document.querySelector('footer p');
    if (footerText) {
        footerText.innerHTML = '<?php echo $config['footer_text']; ?>';
    }
});
</script> 