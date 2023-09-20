<?php
use Adianti\Database\TRecord;

class Entrada extends TRecord
{
    const TABLENAME = 'entrada';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('tp_entrada');
        parent::addAttribute('produto_id');
        parent::addAttribute('data_entrada');
        parent::addAttribute('quantidade');
        parent::addAttribute('fornecedor_id');
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
    public function get_tipo()
    {
        return Tipo_Entrada::find($this->tp_entrada);
    }
}
