<?php

namespace App\controllers;

use ProjetoMvc\render\Action;
use App\model\Usuario;
use App\model\TipoUsuario;
use App\model\Data_Validator;

if (! defined('ABSPATH')){
    header("Location: /");
    exit();
}

class UsuarioController extends Action
{

    public function __construct()
    {
        $this->autenticacao = true; //verifica controle de acesso
        parent::__construct();
        /**
         * caminho com o arquivo do layout padrão que todas as paginas dessa controller poderá usar
         */
        $this->layoutPadrao = PATH_VIEWS."shared/layoutPadrao";
    }

    /**
     * chama a view de alterar perfil, passando o titulo da página
     * @return void
     * Method GET
     */
    public function pageAlterarPerfil()
    {
        $usu = new Usuario();
        $usu->setId($_SESSION["_idusuario"]);
        $usu->carregarInformacoes();

        $this->dados->informacoes = $usu;
        $this->dados->input_drop = true;
        $this->dados->validation = true;
        $this->dados->title = "Alterar Perfil";
        $this->render('alterar-perfil.php');
    }

    public function pageAlterarSenha() {
        $this->dados->title = "Alterar Senha";
        $this->dados->validation = true;
        $this->render('alterar-senha.php');
    }

    public function requestAlterarSenha() {
        $validate = new Data_Validator();
        $usuario = new Usuario();

        $senha_atual = trim(filter_input(INPUT_POST, 'senha_atual', FILTER_SANITIZE_SPECIAL_CHARS));
        $senha_nova = trim(filter_input(INPUT_POST, 'senha_nova', FILTER_SANITIZE_SPECIAL_CHARS));
        $senha_nova2 = trim(filter_input(INPUT_POST, 'senha_nova2', FILTER_SANITIZE_SPECIAL_CHARS));
        $token = trim(filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS));

        $validate
            ->set("senha atual", $senha_atual)->is_required()->max_length(30)
            ->set("\"nova senha\"", $senha_nova)->is_required()->max_length(30)
            ->set("\"repita senha\"", $senha_nova2)->is_required()->max_length(30)
            ->set("token", $token)->is_required();

        if ($validate->validate()) {

            if (strcmp($senha_nova, $senha_nova2) === 0) {

                if (password_verify(TOKEN_SESSAO, $token)) {

                    $usuario->setId($_SESSION["_idusuario"]);
                    $usuario->setSenha($senha_nova);

                    if ($usuario->alterarSenha($senha_atual))
                        $this->setRetorno("Senha alterada com sucesso", true, true);
                    else if($usuario->getRetorno()["exibir"])
                        $this->setRetorno($usuario->getRetorno()["mensagem"], $usuario->getRetorno()["exibir"], $usuario->getRetorno()["status"]);
                    else
                        $this->setRetorno("Não foi possível alterar a senha, tente novamente", true, false);

                } else
                    $this->setRetorno("Token de autenticação inválido, recarregue a página e tente novamente", true, false);


            } else
                $this->setRetorno("As senhas informadas não batem", true, false);


        } else {
            $array_erros = $validate->get_errors();
            $array_erro = array_shift($array_erros);
            $erro = array_shift($array_erro);
            $this->setRetorno($erro, true, false);
        }

        $this->dados->retorno = $this->getRetorno();
        $this->pageAlterarSenha();
    }

    public function requestAlterarPerfil() {
        $validate = new Data_Validator();
        $usuario = new Usuario();

        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
        $apelido = trim(filter_input(INPUT_POST, 'apelido', FILTER_SANITIZE_SPECIAL_CHARS));
        $token = trim(filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS));
        $file = !empty($_FILES["avatar"]["tmp_name"]) ? $_FILES["avatar"] : array();

        $validate->define_pattern('erro_');
        $validate
            ->set("nome", $nome)->is_required()
            ->set("token", $token)->is_required();

        if ($validate->validate()) {

            if (password_verify(TOKEN_SESSAO, $token)) {

                if (!empty($file)) {

                    if (!empty($file["error"]) && $file["error"] !== 4) {

                        if ($file["error"] === 1 || $file["error"] === 2)
                            $this->setRetorno("O arquivo \"". $file["name"] ."\" excede o tamanho máximo permitido de 1,5MB.", true, false);
                        elseif($file["error"] === 3)
                            $this->setRetorno("Não foi possível fazer o upload completo do arquivo, tente novamente", true, false);
                        elseif($file["error"] === 6)
                            $this->setRetorno("Não foi possível fazer o upload do arquivo (pasta temporária ausente)", true, false);
                        else
                            $this->setRetorno("Erro inesperável no upload do arquivo, tente novamente", true, false);

                        $erro_img = true;
                    } else if($file["size"] > 1572864) {
                        $this->setRetorno("O arquivo \"" . $file["name"] . "\" excede o tamanho máximo permitido de 1,5MB.", true, false);
                        $erro_img = true;
                    }elseif(strcmp('image/png', $file["type"]) !== 0 && strcmp('image/gif', $file["type"]) !== 0 && strcmp('image/jpeg', $file["type"]) !== 0) {
                        $this->setRetorno("O Tipo do arquivo enviado é inválido. São permitidoPor favor, envie um arquivo do tipo \"jpeg, png ou gif\"", true, false);
                        $erro_img = true;
                    }

                }

                if (empty($erro_img)) {

                    $usuario->setNome($nome);
                    $usuario->setApelido($apelido);
                    $usuario->setFileAvatar($file);
                    $usuario->setId($_SESSION["_idusuario"]);

                    if ($usuario->alterarPerfil())
                        $this->setRetorno("Perfil foi alterado com sucesso", true, true);
                    else if($usuario->getRetorno()["exibir"])
                        $this->setRetorno($usuario->getRetorno()["mensagem"], $usuario->getRetorno()["exibir"], $usuario->getRetorno()["status"]);
                    else
                        $this->setRetorno("Não foi possível alterar seu perfil, tente novamente", true, false);

                }

            } else
                $this->setRetorno("Token de autenticação inválido, recarregue a página e tente novamente", true, false);

        } else {
            $array_erros = $validate->get_errors();
            $array_erro = array_shift($array_erros);
            $erro = array_shift($array_erro);
            $this->setRetorno($erro, true, false);
        }

        $this->dados->retorno = $this->getRetorno();
        $this->pageAlterarPerfil();

    }

    public function pageGerenciarUsuarios() {
        $usuario = new Usuario();
        $tipo = new TipoUsuario();

        $pagina_atual = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
        $pagina_atual = empty($pagina_atual) ? 1 : $pagina_atual;

        $qtde_registros = 5;

        /*definição de paginação*/

        // Constantes de configuração
        $this->dados->paginacao = new \stdClass;
        $this->dados->paginacao->registros_paginas = $qtde_registros;
        $this->dados->paginacao->pagina_atual = $pagina_atual;
        $this->dados->paginacao->range_paginas = 2; // quantas paginas será exibida ao redor da página atual

        // Calcula a linha inicial da consulta */
        $inicio_registro = ($pagina_atual -1) * $qtde_registros;


        // Consulta dos registros utilizando o LIMIT
        $this->dados->registros = $usuario->paginacao($inicio_registro, $qtde_registros);
        // Total de registros
        $this->dados->paginacao->total_registros = $usuario->totalRegistros();

        $this->dados->tipo_usuario = $tipo->carregarTipoUsuario();
        $this->dados->todosTipos = $tipo->listarTodos();

        $this->dados->title = "Gerenciar usuários";
        $this->dados->validation = true;
        $this->render('gerenciar-usuarios.php');
    }

    public function requestNovoUsuario() {
        $validate = new Data_Validator();
        $usu = new Usuario();

        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS));
        $usuario = trim(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS));
        $senha_nova = trim(filter_input(INPUT_POST, 'senha_nova', FILTER_SANITIZE_SPECIAL_CHARS));
        $senha_nova2 = trim(filter_input(INPUT_POST, 'senha_nova2', FILTER_SANITIZE_SPECIAL_CHARS));
        $token = trim(filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS));
        $id_tipo = filter_input(INPUT_POST, "sel_tipo", FILTER_VALIDATE_INT);

        $validate->define_pattern('erro_');
        $validate
            ->set("nome", $nome)->is_required()
            ->set("email", $email)->is_required()->is_email()
            ->set("tipo de usuario", $id_tipo)->is_required()
            ->set("usuario", $usuario)->is_required()
            ->set("\"nova senha\"", $senha_nova)->is_required()->max_length(30)
            ->set("\"repita senha\"", $senha_nova2)->is_required()->max_length(30)
            ->set("token", $token)->is_required();

        if ($validate->validate()) {

            if (strcmp($senha_nova, $senha_nova2) === 0) {

                if (password_verify(TOKEN_SESSAO, $token)) {

                    $usu->setNome($nome);
                    $usu->setEmail($email);
                    $usu->setLogin($usuario);
                    $usu->setSenha($senha_nova);
                    $usu->setTipo($id_tipo);

                    if ($usu->inserir())
                        $this->setRetorno("Usuário cadastrado com sucesso", true, true);
                    else if(!empty($usu->getRetorno()["exibir"]))
                        $this->setRetorno($usu->getRetorno()["mensagem"], $usu->getRetorno()["exibir"], $usu->getRetorno()["status"]);
                    else
                        $this->setRetorno("Não foi possível cadastrar o usuário, tente novamente", true, false);

                } else
                    $this->setRetorno("Token de autenticação inválido, recarregue a página e tente novamente", true, false);


            } else
                $this->setRetorno("As senhas informadas não batem", true, false);


        } else {
            $array_erros = $validate->get_errors();
            $array_erro = array_shift($array_erros);
            $erro = array_shift($array_erro);
            $this->setRetorno($erro, true, false);
        }

        if (empty($this->getRetorno()["status"])) { //devove os parametros passados para view, e coloca nos campos correspondente

            $this->dados->parametros =
                array(
                    "param_nome" => $nome,
                    "param_email" => $email,
                    "param_tipo" => $id_tipo,
                    "param_usuario" => $usuario
                );

        }

        $this->dados->retorno = $this->getRetorno();
        $this->pageGerenciarUsuarios();

    }

}