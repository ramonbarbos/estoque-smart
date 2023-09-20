<?php
use Adianti\Database\TRecord;

class Usuario extends TRecord
{
    const TABLENAME = 'usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('nome');
        parent::addAttribute('login');
        parent::addAttribute('senha');
        parent::addAttribute('cargo');
        parent::addAttribute('ativo');

        // Configurar os campos de timestamps
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');

        // Configurar os timestamps para atualização automática
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    // Sobrescreva o método store para definir a data de atualização
    public function store()
    {
        $this->updated_at = date('Y-m-d H:i:s');
        parent::store();
    }
}
