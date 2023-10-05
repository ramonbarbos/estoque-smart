<?php

use Adianti\Database\TRecord;

class Estoque extends TRecord
{
    const TABLENAME = 'estoque';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('produto_id');
        parent::addAttribute('entrada_id');
        parent::addAttribute('saida_id');
        parent::addAttribute('fornecedor_id');
        parent::addAttribute('cliente_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('nota_fiscal');
        parent::addAttribute('preco_unit');
        parent::addAttribute('valor_total');
        parent::addAttribute('updated_at');
        parent::addAttribute('created_at');


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
    public function get_saida()
    {
        return Saida::find($this->saida_id);
    }
    public function get_cliente()
    {
        return Cliente::find($this->cliente_id);
    }
  
}
