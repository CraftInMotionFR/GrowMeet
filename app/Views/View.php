<?php

class View {
    private string $template;
    private array $data;

    public function render(string $template, array $data = []): void {
        $this->template = $template;
        $this->data = $data;

        // On extrait les variables du tableau pour les rendre disponibles dans le template, exemple : ['articles' => $x] → $articles = $x
        extract($this->data);

        // On capture le contenu du template
        ob_start();
        require __DIR__ . '/templates/' . $this->template . '.php';
        $content = ob_get_clean();

        // On inclut le layout qui utilise $content
        require __DIR__ . '/templates/layout.php';
    }
}