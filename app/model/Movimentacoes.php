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
        parent::addAttribute('tipo');
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('preco_unit');
        parent::addAttribute('saldo_anterior');
        parent::addAttribute('saldo_atual');
        parent::addAttribute('responsavel_id');

        // Configurar os campos de timestamps
        parent::addAttribute('created_at');

        $this->created_at = date('Y-m-d H:i:s');
    }
    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }
    public function get_user()
    {
        return SystemUser::find($this->responsavel_id);
    }
    public function get_tipo()
    {
        return Tipo_Entrada::find($this->tipo);
    }
    // Sobrescreva o método store para definir a data de atualização
    public function store()
    {
        parent::store();
    }
}
