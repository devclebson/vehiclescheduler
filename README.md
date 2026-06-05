# 🚗 GLPI Vehicle Scheduler (Gestão de Frotas)

**Versão:** 4.0.0  
**Compatibilidade:** GLPI 11.x  
**Idioma:** 100% PT-BR Nativo (com suporte a i18n Gettext)

O **Vehicle Scheduler** é um plugin completo e altamente customizado para transformar o seu GLPI em um verdadeiro sistema de **Gestão de Frotas (Fleet Management)** de nível Enterprise. Ele substitui os controles em planilhas por fluxos de trabalho inteligentes, painéis modernos e monitoramento proativo.

---

## 🌟 Principais Recursos

### 1. 📊 Dashboards Avançados
O plugin possui uma arquitetura de visão dupla para se adaptar a diferentes cenários de uso:
- **Management Dashboard (`management.php`)**: Um painel operacional limpo, com design corporativo (SaaS Style) onde o gestor pode atuar ativamente. Possui atalhos rápidos, listas de manutenções em atraso, aprovação rápida de reservas e alertas de CNH com um clique.
- **NOC / TV Dashboard (`dashboard.php`)**: Um painel em formato *Dark Mode* projetado especificamente para ser exibido em TVs do setor. Possui atualização automática (Auto-refresh de 60s) e botão nativo de "Tela Cheia" que oculta toda a interface do GLPI, garantindo foco 100% no monitoramento de Incidentes, Reservas Pendentes e Manutenções Atrasadas.

### 2. 🪪 Gestão de Motoristas e Veículos
- Cadastro completo de motoristas com acompanhamento de validade da CNH e categoria. Alertas automáticos aparecem no dashboard 90 dias antes do vencimento.
- Cadastro de veículos incluindo placa, modelo, status (ativo/inativo) e restrições.

### 3. 🗓️ Sistema de Reservas
- Fluxo de solicitação de uso de veículos com origem, destino e justificativa.
- Nível de aprovação: Colaboradores podem solicitar, Gestores aprovam ou recusam com apenas um clique pelo painel principal.
- Integração com calendário para visualização da disponibilidade da frota.

### 4. ⚠️ Gestão de Incidentes e Sinistros
- Registro detalhado de incidentes em rota (Acidentes, Falhas Mecânicas, Avarias).
- Abertura formal de Sinistros (Insurance Claims) para controle de franquia, datas de entrada e saída de oficina e seguradora responsável.
- Gestão e registro de multas associadas aos motoristas.

### 5. 🔧 Manutenções Preventivas e Corretivas
- Cronograma de oficina! Saiba exatamente o que precisa ser reparado.
- Os alertas do painel avisam instantaneamente quando a data limite de uma manutenção foi ultrapassada ("⚠️ Em Atraso").

### 6. 🛡️ Perfis e Permissões Seguras
Todo o sistema obedece rigorosamente às matrizes de perfil do GLPI.
- **Usuários Comuns (Colaboradores)**: Têm acesso a uma interface simplificada onde só podem solicitar reservas, visualizar o status de suas próprias solicitações e abrir pequenos incidentes. Sem acesso às áreas gerenciais.
- **Gestores / Técnicos**: Acesso total de leitura e escrita aos painéis de dashboards, cadastro e relatórios.

### 7. 🎨 UI/UX Moderna
Diferente da interface padrão engessada do GLPI, este plugin foi remodelado usando CSS Moderno, Glassmorphism, botões interativos e tipografia legível (padrões de SaaS). Cada formulário conta com botões inteligentes de "Voltar" que respeitam o histórico de navegação.

---

## 🛠️ Instalação

1. Acesse o diretório de plugins do seu servidor:
   ```bash
   cd /var/www/glpi/plugins/
   ```
2. Descompacte a pasta do plugin. O nome da pasta deve ser exatamente `vehiclescheduler`.
3. Acesse a interface web do GLPI com uma conta de *Super-Admin*.
4. Vá em **Configurar > Plug-ins**.
5. Localize o "Gestão de Frotas", clique em **Instalar** e em seguida em **Ativar**.
6. Atribua os direitos de acesso adequados aos seus perfis em **Administração > Perfis > (Escolha o perfil) > Gestão de Frota**.

---

## 🌐 Internacionalização (i18n)

Este plugin é construído com base nas boas práticas do GLPI (`__('Texto', 'vehiclescheduler')`). Todo o mapeamento de tradução foi feito e compilado via Gettext.
Os arquivos de idiomas oficiais encontram-se em:
- `/locales/pt_BR.po` (Código-fonte das traduções)
- `/locales/pt_BR.mo` (Dicionário binário usado pelo GLPI)

Para gerar novas traduções no futuro, edite o `.po` e recompile o `.mo` usando o utilitário `msgfmt`.

---
*Desenvolvido e mantido para a comunidade GLPI com foco em alta eficiência.*
