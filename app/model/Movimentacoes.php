<?php
use Adianti\Database\TRecord;

class Movimentacoes extends TRecord
{
    const TABLENAME = 'movimentacoes';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('data_hora');
        parent::addAttribute('descricao');
        parent::addAttribute('quantidade');
        parent::addAttribute('valor_total');
        parent::addAttribute('saldoEstoque');
        parent::addAttribute('produto_id');
        parent::addAttribute('responsavel_id');

        // Configurar os campos de timestamps
        parent::addAttribute('created_at');

        $this->created_at = date('Y-m-d H:i:s');
    }

    // Sobrescreva o método store para definir a data de atualização
    public function store()
    {
        parent::store();
    }
}
