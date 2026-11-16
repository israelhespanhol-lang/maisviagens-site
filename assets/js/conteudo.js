/*
  CONTEÚDO EDITÁVEL DO SITE MAIS VIAGENS
  ---------------------------------------
  Este é o único arquivo que a equipe precisa abrir para trocar contatos,
  mensagens de WhatsApp e caravanas.
  Regras simples:
  - Mantenha as aspas "..." em volta de cada texto.
  - Cada item de lista termina com vírgula, exceto o último (que também pode ter).
  - Para retirar uma caravana, apague o bloco { ... } inteiro dela.
  - Para adicionar, copie um bloco existente e altere os campos.
*/
window.MAIS_VIAGENS = {
  versao: "VERSÃO 23 · 23/10/2026",

  contato: {
    // Somente números, com 55 + DDD.
    whatsapp: "5548999697274",
    whatsappExibicao: "(48) 99969-7274",
    instagram: "agencia_maisviagens",     // sem @
    email: "",                            // opcional
    cidade: ""                            // opcional. Ex.: "Criciúma · SC"
  },

  // Canal "Grupo VIP" no WhatsApp. Não está em uso no momento: o botão verde flutuante abre o WhatsApp do atendimento.
  grupoVip: "https://whatsapp.com/channel/0029Vb9F9e9FCCodDAZfDt0v",

  // Endereço que recebe os formulários (Formspree, Make, RD Station, planilha etc.).
  // Vazio = o formulário prepara a mensagem e abre o WhatsApp da agência.
  formularioEndpoint: "",

  // Mensagem inicial do WhatsApp em cada botão (identifica a origem do contato).
  mensagensWhatsapp: {
    inicio: "Olá, Mais Viagens! Vim pelo site e quero planejar uma viagem.",
    personalizadas: "Olá, Mais Viagens! Quero uma proposta de viagem personalizada.",
    caravanas: "Olá, Mais Viagens! Tenho uma dúvida sobre as caravanas.",
    orlando: "Olá, Mais Viagens! Quero planejar minha viagem para Orlando.",
    carros: "Olá, Mais Viagens! Quero uma cotação de aluguel de carro.",
    novaYork: "Olá, Mais Viagens! Quero um orçamento do pacote para Nova York.",
    europa: "Olá, Mais Viagens! Quero um orçamento do pacote para a Europa.",
    contato: "Olá, Mais Viagens! Vim pelo site.",
    promocoes: "Olá, Mais Viagens! Vi a aba Promoções e gostaria de conhecer as oportunidades disponíveis.",
    promocoesHoteis: "Olá, Mais Viagens! Vi a aba Promoções e gostaria de consultar hotéis e hospedagens.",
    promocoesPasseios: "Olá, Mais Viagens! Vi a aba Promoções e gostaria de consultar passeios e experiências.",
    promocoesCarros: "Olá, Mais Viagens! Vi a aba Promoções e gostaria de consultar ofertas de aluguel de carros."
  },

  // CARAVANAS — aparecem na página inicial e na aba Caravanas.
  // tipo: "ultravel" mostra o aviso de site externo antes de abrir o link;
  // tipo: "atendimento" abre o WhatsApp do atendimento com a mensagem indicada.
  caravanas: [
    {
      nome: "Paraguai — Fronteira de Negócios",
      foto: "assets/img/caravana-paraguai.jpg",
      link: "https://grupos.ultravel.com.br/e/fronteira-de-negocios-edicao-paraguai",
      tipo: "ultravel",
      data: "",                       // opcional. Ex.: "Março 2027" (aparece como selo no cartão)
      botao: "Acessar a página da imersão no Paraguai"
    },
    {
      nome: "Israel com Pr Matheus Poderoso e Pra Raquel Poderoso",
      foto: "assets/img/caravana-israel.jpg",
      link: "https://grupos.ultravel.com.br/e/caravana-para-israel-com-pr-matheus-poderoso-e-pra-raquel-poderoso",
      tipo: "ultravel",
      data: "Junho/Julho 2027",
      botao: "Acessar a página da caravana para Israel"
    },
    {
      nome: "THE SOURCE",
      foto: "assets/img/caravana-the-source.jpg",
      // Grupo dos jovens (fora de uso): https://chat.whatsapp.com/CjL1fNyZyJ748mVKhG7XRW?mode=gi_t
      tipo: "atendimento",
      data: "2027",
      mensagem: "Olá, Mais Viagens! Quero saber sobre a caravana THE SOURCE.",
      botao: "Falar com atendente"
    }
  ],

  // DEPOIMENTOS — aparecem na página inicial SOMENTE quando houver pelo menos um.
  // Use apenas depoimentos reais, com autorização do cliente.
  // foto é opcional (coloque a imagem em assets/img/).
  depoimentos: [
    /*
    {
      texto: "Escreva aqui o depoimento do cliente, com as palavras dele.",
      nome: "Nome do cliente",
      destino: "Orlando",
      foto: "assets/img/orlando-castelo.jpg"
    },
    */
  ],

  // TRAVELPAYOUTS — INTEGRAÇÃO DE PROMOÇÕES
  // A chave da API NÃO deve ser inserida aqui. Ela deve ser registrada no
  // servidor HostGator (variável de ambiente ou arquivo privado fora da web).
  travelpayouts: {
    // URL de busca de passagens, COPIADA do painel Travelpayouts.
    // Use o link gerado na sua conta para garantir a atribuição da comissão.
    // Se estiver vazio, os cartões de voos levam ao WhatsApp da agência.
    linkBuscaPassagens: "",

    // PROMOÇÕES AUTOMATICAMENTE CONVERTIDAS PELA LINKS API:
    // Cadastre as ofertas em assets/data/promocoes-parceiros.json.
    // O servidor PHP usa API Token, Marker e Project ID sem revelar as credenciais.
    // Os campos abaixo são uma forma LEGADA de cadastrar links afiliados prontos.
    // Promoções de outros programas: sempre utilizar links de afiliado
    // obtidos em Tools > Links dentro do projeto Mais Viagens.
    // Não cadastrar valores estimados ou preços sem comprovação.
    // Exemplo de preenchimento (remova /* e */ e substitua pelos dados reais):
    promocoes: [
      /*
      {
        tipo: "hoteis",                       // passagens | hoteis | passeios | carros
        titulo: "Hospedagem em [destino]",
        descricao: "Condições divulgadas pelo parceiro.",
        imagem: "assets/img/viagem-personalizada-roma.jpg",
        preco: "",                          // opcional: texto de preço verificado
        validade: "",                       // opcional: data/observação de validade
        url: "https://LINK-GERADO-NA-SUA-CONTA"
      }
      */
    ],

    // A busca Aviasales em pt-BR e BRL já está em promocoes.html (tpemd.com).
    // Para OUTROS widgets, cole apenas a URL src oficial (tp.media).
    // NÃO cole o HTML inteiro. O próprio site carregará os scripts seguros.
    // Pode adicionar 1 widget por categoria ou quantos quiser.
    widgets: [
      // { titulo: "Buscar passagens aéreas", descricao: "Compare destinos e datas.", src: "https://tp.media/content?..." },
      // { titulo: "Encontrar hotéis", descricao: "Pesquise hospedagens.", src: "https://tp.media/content?..." }
    ]
  },

  // Medição: preencha um dos dois (ou deixe vazio para não carregar nada).
  medicao: {
    googleTagManager: "", // Ex.: "GTM-XXXXXXX"
    googleAnalytics: ""   // Ex.: "G-XXXXXXXXXX"
  }
};
