<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class RetornoFornecedorForm extends TPage
{
    private $form;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['RetornoFornecedorList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Retorno_Fornecedor');

        // Cria um array com as opções de escolha


        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_retorno');
        $this->form->setFormTitle('Retorno');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);

        try {
            TTransaction::open('sample');
        
                $criteria = new TCriteria;
                $criteria->add(new TFilter('quant_retirada', '>', 0));
                $criteria->setProperty('order', 'id');
                // Use o critério para buscar os registros do banco de dados
                $repository = new TRepository('Estoque');
                $items = $repository->load($criteria);

                // Crie um array de itens no formato chave-valor
                $options = [];
                foreach ($items as $item) {
                    $options[$item->id] = $item->id;
                }

            TTransaction::close(); // Feche a transação quando terminar
        } catch (Exception $e) {
            // Lida com exceções aqui, como TTransaction não pode ser aberto
            echo 'Erro: ' . $e->getMessage();
            TTransaction::rollback(); // Se ocorrer um erro, faça rollback na transação
        }


        // Criação de fields
        $id = new TEntry('id');
        $entrada = new TDBCombo('entrada_id', 'sample', 'Estoque', 'id', 'entrada_id');
        $entrada->setChangeAction(new TAction([$this, 'onProdutoChange']));
        $entrada->setId('form_entrada');
        $produto = new TEntry('produto_id');
        $produto->setId('form_produto');
        $produto_nome = new TEntry('produto_nome');
        $produto_nome->setId('form_produto_nome');
        $datas    = new TDate('data_retorno');
        $datas->setId('form_data');
        $fornecedor = new TEntry('fornecedor_id');
        $fornecedor->setId('form_fornecedor');
        $fornecedor_nome    = new TEntry('fornecedor_nome');
        $fornecedor_nome->setId('form_fornecedor_nome');
        $nf = new TEntry('nota_fiscal');
        $nf->setId('form_nota_fiscal');
        $qtd_disponivel = new TEntry('quantidade_disponivel');
        $qtd_disponivel->setId('form_quantidade_disponivel');
        $valor_disponivel = new TEntry('valor_disponivel');
        $valor_disponivel->setId('form_valor_disponivel');

        $motivo = new TText('motivo');

        $valor = new TEntry('preco_unit');
        $valor->setProperty('onkeyup', 'calcularValorTotal()');
        $valor->setId('form_preco_unit');

        $qtd = new TEntry('quantidade');
        $qtd->setId('form_quantidade');
        $qtd->setProperty('onkeyup', 'calcularValorTotal()');
        $total = new TEntry('valor_total');
        $total->setId('form_valor_total');
        $total->setProperty('onkeyup', 'calcularValorTotal()');

        $entrada->addItems($options);


        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Estoque')], [$entrada],);
        $this->form->addFields([new TLabel('Produto')], [$produto, $produto_nome],);
        $this->form->addFields([new TLabel('Nota Fiscal')], [$nf]);
        $this->form->addFields([new TLabel('Data de Retorno')], [$datas]);
        $this->form->addFields([new TLabel('Fornecedor')], [$fornecedor_nome, $fornecedor]);
        $this->form->addFields([new TLabel('Quantidade Disponível')], [$qtd_disponivel]);
        $this->form->addFields([new TLabel('Quantidade')], [$qtd]);
        $this->form->addFields([new TLabel('Valor Disponível')], [$valor_disponivel]);
        $this->form->addFields([new TLabel('Valor unidade')], [$valor]);
        $this->form->addFields([new TLabel('Total')], [$total]);
        $this->form->addFields([new TLabel('Motivo')], [$motivo]);

        // Validação do campo Nome
        $entrada->addValidation('Estoque', new TRequiredValidator);
        $fornecedor->addValidation('Fornecedor', new TRequiredValidator);
        $datas->addValidation('Data de Retorno', new TRequiredValidator);
        $motivo->addValidation('Motivo', new TRequiredValidator);

        // Tornar o campo ID não editável
        $id->setEditable(false);
        $nf->setEditable(false);
        $qtd_disponivel->setEditable(false);
        $valor_disponivel->setEditable(false);
        $valor->setEditable(false);
        $fornecedor_nome->setEditable(false);
        $fornecedor->style = 'display: none;';

        // Tamanho dos campos
        $id->setSize('100%');
        $entrada->setSize('50%');
        $entrada->enableSearch();
        $fornecedor->setSize('100%');
        //$datas->setDatabaseMask('yyyy-mm-dd');
        $datas->setMask('dd/mm/yyyy');
        $nf->setNumericMask(2, '', '', true);
        // $qtd_disponivel->setRange(0, 100, 1);
        $qtd_disponivel->setValue('0');
        $qtd->setValue('0');
        $valor_disponivel->setNumericMask(2, ',', '.', true);
        $valor_disponivel->setValue('0,00');

       // $valor->setNumericMask(2, ',', '.', true);
        $valor->setSize('100%');
        $valor->setValue('0,00');
        $total->setValue('0,00');
        //$total->setNumericMask(2, ',', '.', true);

        $motivo->setSize('100%');


        TScript::create('function calcularValorTotal() {
            var quantidadeField = document.getElementById("form_quantidade");
            var valorUnitarioField = document.getElementById("form_preco_unit");
        
            if (quantidadeField && valorUnitarioField) {
                var quantidade = parseFloat(quantidadeField.value.replace(/\./g, ""));
                var valorUnitario = parseFloat(valorUnitarioField.value);
        
                console.log("Quantidade:", quantidade);
                console.log("Valor Unitário:", valorUnitario);
        
                if (!isNaN(quantidade) && !isNaN(valorUnitario)) {
                    var valorTotal = quantidade * valorUnitario;
                    var formattedTotal = formatarNumero(valorTotal);
                    document.getElementById("form_valor_total").value = formattedTotal;
        
                }
            }
        }
        
        function formatarNumero(numero) {
            var numeroFormatado = numero.toFixed(2);
            console.log("Valor Total:", numeroFormatado);
            return numeroFormatado;
        }');



        // Adicionar botão de salvar
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:plus green');
        $btn->class = 'btn btn-sm btn-primary';

        // Adicionar link para criar um novo registro
        $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');

        // Adicionar link para fechar o formulário
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        // Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }



    public static function onProdutoChange($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['entrada_id'])) {
                $entrada_id = $param['entrada_id'];

                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                $entrada = new Estoque($entrada_id);
                $nota_fiscal = $entrada->nota_fiscal;
                $produto = $entrada->produto_id;
                $produto_nome = $entrada->produto->nome;
                $fornecedor_id = $entrada->fornecedor_id;
                $fornecedor_nome = $entrada->fornecedor->nome;
                $qtd_disponivel = $entrada->quantidade;
                $valor_disponivel = $entrada->valor_total; // Suponhamos que o campo no banco de dados se chama "valor"
                $valor = $entrada->preco_unit; // Suponhamos que o campo no banco de dados se chama "valor"


                TTransaction::close();

                // Preencha o campo de Nota Fiscal com o valor obtido
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_valor_disponivel').value = '{$valor_disponivel}';");
                TScript::create("document.getElementById('form_preco_unit').value = '{$valor}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto}';");
                TScript::create("document.getElementById('form_produto_nome').value = '{$produto_nome}';");
                TScript::create("document.getElementById('form_fornecedor').value = '{$fornecedor_id}';");
                TScript::create("document.getElementById('form_fornecedor_nome').value = '{$fornecedor_nome}';");
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    public function onSave($param)
    {
        try {
            TTransaction::open('sample');

            $retorno = new Retorno_Fornecedor();
            $retorno->fromArray($param);

            $data_retorno = DateTime::createFromFormat('d/m/Y', $retorno->data_retorno);
            if ($data_retorno) {
                $retorno->data_retorno = $data_retorno->format('Y-m-d');
            }

            $existente = Saida::where('id', '=', $retorno->id)
                ->count();

            if ($existente == 0) {
                $this->atualizarEstoque($retorno->entrada_id, $retorno->quantidade);
                $retorno->store(); // Salva a saída no banco de dados

                TTransaction::close();
            } else {
                $retorno->store(); // Salva a saída no banco de dados

            }

            $this->createMovement($retorno);


            TTransaction::close();
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function createMovement($retorno)
    {
        try {
            TTransaction::open('sample');

            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $prod = new Produto($retorno->produto_id);
            $usuario_logado = TSession::getValue('userid');
            $desc = 'Devolução - ' .$retorno->fornecedor->nome;
            $descricao = substr($desc, 0, 30) . '...'; 
            $mov->data_hora = date('Y-m-d H:i:s');
            $mov->descricao = $descricao;
            $mov->valor_total = $retorno->valor_total;
            $mov->produto_id = $retorno->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->quantidade = $retorno->quantidade;

            $estoque = Estoque::where('produto_id', '=', $retorno->produto_id)->first();
            if ($estoque->valor_total) {
                $mov->saldoEstoque = $estoque->valor_total;
            } else {
                $mov->saldoEstoque = 0;
            }
            $mov->store();
            TTransaction::close();

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    private function atualizarEstoque($entrada_id, $quantidadeVendida)
    {
        try {
            TTransaction::open('sample');

            $entrada = new Estoque($entrada_id);

            // Verifica se a quantidade em estoque é maior ou igual à quantidade vendida
            if ($entrada->quantidade >= $quantidadeVendida && !$this->estoqueAtualizado) {
                // Calcula a nova quantidade em estoque após a venda
                $novaQuantidade = $entrada->quantidade - $quantidadeVendida;
                $valorVendido = $entrada->preco_unit * $quantidadeVendida;
                $novoValor = $entrada->valor_total - $valorVendido;
                $entrada->valor_total = $novoValor;
                // Atualiza a quantidade em estoque no objeto
                $entrada->quantidade = $novaQuantidade;
                $entrada->quant_retirada = $novaQuantidade;
                // Atualiza o registro no banco de dados
                $entrada->store();
                // Atualiza o campo de quantidade disponível no formulário usando JavaScript
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$novaQuantidade}';");
                $this->estoqueAtualizado = true;
            } elseif ($entrada->quantidade < $quantidadeVendida) {
                throw new Exception('Quantidade solicitada atingiu o limite do estoque.');
                TTransaction::close();
            } else {
                // Se a quantidade em estoque for insuficiente, você pode lançar uma exceção ou tratar de acordo com sua lógica de negócios
                TTransaction::rollback();
                throw new Exception('Quantidade em estoque insuficiente.');
            }

            // Comita a transação após o sucesso
            TTransaction::close();
        } catch (Exception $e) {
            // Trate a exceção de acordo com suas necessidades
            TTransaction::rollback();
            throw $e;
        }
    }

    public  function onEdit($param)
    {
        try {

            if (isset($param['key'])) {

                TTransaction::open('sample');
                $key = $param['key']; // A chave primária do registro que está sendo editado
                $retorno = new Retorno_Fornecedor($key);

                $entrada_id = $retorno->entrada_id;

                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                $entrada = new Entrada($entrada_id);
                $nota_fiscal = $entrada->nota_fiscal;
                $qtd_disponivel = $entrada->quantidade;

                $produto_id = $retorno->produto_id;
                $produto_nome = $retorno->produto->nome;
                $data = $retorno->data_retorno;
                $fornecedor = $retorno->fornecedor_id;
                $fornecedor_nome = $retorno->fornecedor->nome;
                $qtd = $retorno->quantidade;
                $this->form->setData($retorno);
                $this->form->getField('quantidade')->setEditable(false);

                // Use a função date_format para formatar a data
                $data = date_format(date_create($retorno->data_retorno), 'd/m/Y');

                // Configure o campo de data com a máscara 'dd/mm/yyyy'
                $dataField = $this->form->getField('data_retorno');
                $dataField->setMask('dd/mm/yyyy');

                $novoValor = $entrada->valor_total;
                $valor_formatado = number_format($novoValor, 2, ',', '.');

                TTransaction::close();

                // Preencha os campos do formulário com os valores obtidos
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto_id}';");
                TScript::create("document.getElementById('form_produto_nome').value = '{$produto_nome}';");
                TScript::create("document.getElementById('form_data').value = '{$data}';");
                TScript::create("document.getElementById('form_fornecedor').value = '{$fornecedor}';");
                TScript::create("document.getElementById('form_fornecedor_nome').value = '{$fornecedor_nome}';");
                TScript::create("document.getElementById('form_quantidade').value = '{$qtd}';");

                TScript::create("document.getElementById('form_valor_disponivel').value = '{$valor_formatado}';");
            } else {
                // Lida com a situação em que 'key' não está definida, por exemplo, exibir uma mensagem de erro
                error_log('Chave primária ausente.');
            }


            // Resto do código para editar o registro
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    // Método fechar
    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
