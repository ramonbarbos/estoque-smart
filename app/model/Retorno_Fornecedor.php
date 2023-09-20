<?php
use Adianti\Database\TRecord;

class Retorno_Fornecedor extends TRecord
{
    const TABLENAME = 'retorno_fornecedor';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('entrada_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('data_retorno');
        parent::addAttribute('motivo');
        parent::addAttribute('quantidade');
        parent::addAttribute('fornecedor_id');
        parent::addAttribute('preco_unit');
        parent::addAttribute('valor_total');
    }

    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }
    public function get_fornecedor()
    {
        return Fornecedor::find($this->fornecedor_id);
    }
    public function get_entrada()
    {
        return Entrada::find($this->entrada_id);
    }
}
