<?php
// app/Controllers/ConfigController.php

class ConfigController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // Busca os dados do seu usuário (ID 1)
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = 1");
        $stmt->execute();
        $usuario = $stmt->fetch();

        require_once '../app/Views/configuracoes.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Função para limpar a máscara de moeda
            $limparMoeda = function($valor) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
                return (float) $valor;
            };

            $salario = $limparMoeda($_POST['salario_base']);
            $saldoInicial = $limparMoeda($_POST['saldo_inicial_mes']);

            $stmt = $this->pdo->prepare("UPDATE usuarios SET salario_base = ?, saldo_inicial_mes = ? WHERE id = 1");
            $stmt->execute([$salario, $saldoInicial]);

            header('Location: /financeiro/public/index.php/configuracoes?sucesso=1');
            exit;
        }
    }
}