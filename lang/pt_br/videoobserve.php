<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings em português do Brasil para Video Observation.
 *
 * @package mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addevent'] = 'Adicionar evento';
$string['aggregatereport'] = 'Relatório agregado de observação';
$string['allowcomments'] = 'Permitir observação textual em cada ocorrência';
$string['allowintervals'] = 'Permitir observações por intervalo';
$string['averageperstudent'] = 'Média de marcações por estudante';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['completiondetail:occurrences'] = 'Registrar pelo menos {$a} observação(ões)';
$string['completiondetail:percent'] = 'Assistir pelo menos {$a}% do vídeo';
$string['completionoccurrences'] = 'Exigir quantidade de observações';
$string['completionoccurrences_help'] = 'A atividade só é concluída depois que o estudante registrar esta quantidade de observações. Informe 0 para desativar esta regra.';
$string['completionpercent'] = 'Exigir percentual assistido';
$string['completionpercent_help'] = 'A atividade só é concluída depois que este percentual de conteúdo único do vídeo for assistido. Informe 0 para desativar esta regra.';
$string['deleteeventconfirm'] = 'Excluir este evento da ficha de observação?';
$string['deleteoccurrenceconfirm'] = 'Excluir esta observação?';
$string['deletereferenceconfirm'] = 'Excluir esta observação de referência?';
$string['editevent'] = 'Editar evento';
$string['event'] = 'Evento';
$string['eventdeleted'] = 'Evento de observação excluído.';
$string['eventdescription'] = 'Descrição ou critério de observação';
$string['eventfrequency'] = 'Eventos mais identificados';
$string['eventinuse'] = 'Este evento já possui observações de estudantes e não pode ser excluído. Edite o evento em vez disso.';
$string['eventname'] = 'Nome do evento';
$string['eventrequired'] = 'Evento obrigatório';
$string['eventsaved'] = 'Evento de observação salvo.';
$string['exportcsv'] = 'Exportar CSV';
$string['finalsheet'] = 'Ficha final de observação';
$string['finishinterval'] = 'Finalizar intervalo';
$string['identifiedmoments'] = 'Momentos identificados';
$string['intervalstarted'] = 'Intervalo iniciado.';
$string['invalidtolerance'] = 'A tolerância deve estar entre 0 e 300 segundos.';
$string['invalidurl'] = 'Informe uma URL direta de vídeo válida.';
$string['invalidvimeo'] = 'Informe um ID ou uma URL válida de vídeo do Vimeo.';
$string['invalidyoutube'] = 'Informe um ID ou uma URL válida de vídeo do YouTube.';
$string['manageobservationsheet'] = 'Gerenciar ficha de observação';
$string['manageobservationsheet_help'] = 'Crie os tipos de evento que os estudantes deverão observar. O mesmo evento pode ser marcado quantas vezes aparecer no vídeo.';
$string['managereference'] = 'Observações de referência';
$string['markings'] = 'marcações';
$string['marknow'] = 'Marcar neste momento';
$string['meandeviation'] = 'Desvio médio de tempo';
$string['modulename'] = 'Video Observation';
$string['modulename_help'] = 'Atividade de observação sistemática de vídeo em que o estudante marca eventos recorrentes, momentos ou intervalos e pode adicionar observações.';
$string['modulenameplural'] = 'Observações de vídeo';
$string['mostidentifiedmoments'] = 'Momentos mais identificados';
$string['noactivities'] = 'Não há atividades Video Observation visíveis neste curso.';
$string['noevents'] = 'Nenhum evento foi criado na ficha de observação.';
$string['nomarkingsyet'] = 'Nenhuma observação foi registrada ainda.';
$string['noreference'] = 'Nenhuma observação de referência foi registrada pelo professor.';
$string['nostudents'] = 'Nenhum estudante foi encontrado para o grupo ou atividade atual.';
$string['notavailable'] = 'N/D';
$string['observationnote'] = 'Observação textual';
$string['observations'] = 'Observações';
$string['observationsettings'] = 'Configurações da observação';
$string['observationsheet'] = 'Ficha de observação';
$string['observationtimeline'] = 'Linha do tempo das observações';
$string['player'] = 'Player do vídeo';
$string['pluginadministration'] = 'Administração do Video Observation';
$string['pluginname'] = 'Video Observation';
$string['poster'] = 'Imagem de capa';
$string['privacy:exportpath'] = 'Dados do Video Observation';
$string['privacy:metadata:occurrences'] = 'Armazena os eventos e momentos observados pelo estudante.';
$string['privacy:metadata:occurrences:endtime'] = 'Momento final opcional de uma observação por intervalo.';
$string['privacy:metadata:occurrences:eventtypeid'] = 'Evento da ficha de observação que foi marcado.';
$string['privacy:metadata:occurrences:note'] = 'Observação textual do estudante associada à ocorrência.';
$string['privacy:metadata:occurrences:starttime'] = 'Momento inicial da ocorrência observada.';
$string['privacy:metadata:occurrences:timecreated'] = 'Data de criação da observação.';
$string['privacy:metadata:occurrences:timemodified'] = 'Data da última alteração da observação.';
$string['privacy:metadata:occurrences:userid'] = 'Usuário que registrou a observação.';
$string['privacy:metadata:progress'] = 'Armazena o progresso consolidado de visualização do vídeo.';
$string['privacy:metadata:progress:duration'] = 'Duração conhecida do vídeo.';
$string['privacy:metadata:progress:lastposition'] = 'Última posição salva para retomada.';
$string['privacy:metadata:progress:percent'] = 'Percentual assistido calculado a partir do conteúdo único assistido.';
$string['privacy:metadata:progress:segments'] = 'Trechos consolidados do vídeo realmente assistidos.';
$string['privacy:metadata:progress:timemodified'] = 'Data da última atualização do progresso.';
$string['privacy:metadata:progress:uniquewatched'] = 'Quantidade de segundos diferentes assistidos.';
$string['privacy:metadata:progress:userid'] = 'Usuário cujo progresso é armazenado.';
$string['privacy:metadata:references'] = 'Armazena as observações de referência criadas pelo professor.';
$string['privacy:metadata:references:createdby'] = 'Usuário que criou a observação de referência.';
$string['privacy:metadata:references:endtime'] = 'Momento final esperado opcional.';
$string['privacy:metadata:references:eventtypeid'] = 'Evento representado pela marcação de referência.';
$string['privacy:metadata:references:note'] = 'Observação textual associada à referência.';
$string['privacy:metadata:references:starttime'] = 'Momento inicial esperado do evento.';
$string['privacy:metadata:references:timecreated'] = 'Data de criação da marcação de referência.';
$string['quantity'] = 'Quantidade';
$string['referenceanswer'] = 'Resposta de referência do professor';
$string['referencecount'] = 'Marcações de referência';
$string['referenceextras'] = 'Marcações adicionais';
$string['referenceinstructions'] = 'Assista ao mesmo vídeo e marque os eventos esperados. Essas marcações são usadas para comparação e podem permanecer ocultas dos estudantes.';
$string['referencematches'] = 'Correspondências com a referência';
$string['referencemissed'] = 'Referências não identificadas';
$string['referencetolerance'] = 'Tolerância da referência (segundos)';
$string['referencetolerance_help'] = 'Diferença máxima, em segundos, entre a marcação do estudante e a referência mais próxima do professor para considerar que houve correspondência.';
$string['referencevisible'] = 'A resposta de referência do professor está visível nesta atividade.';
$string['requiredsourceurl'] = 'Informe a URL ou o ID do vídeo para a fonte selecionada.';
$string['requiredvideofile'] = 'Envie um arquivo de vídeo quando a fonte selecionada for arquivo do Moodle.';
$string['resumeplayback'] = 'Retomar a reprodução da última posição salva';
$string['saveerror'] = 'Não foi possível salvar a observação.';
$string['saveevent'] = 'Salvar evento';
$string['secondsvalue'] = '{$a} s';
$string['showreference'] = 'Mostrar a resposta de referência do professor aos estudantes';
$string['sortorder'] = 'Ordem';
$string['sourceupload'] = 'Vídeo enviado ao Moodle';
$string['sourceurl'] = 'URL direta do vídeo';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Iniciar intervalo';
$string['student'] = 'Estudante';
$string['studentdifferences'] = 'Diferenças entre estudantes';
$string['students'] = 'Estudantes';
$string['studentsidentified'] = 'Estudantes que identificaram';
$string['timewindow'] = 'Faixa de tempo';
$string['totalmarkings'] = 'Total de marcações';
$string['trackingerror'] = 'Não foi possível sincronizar o progresso do vídeo agora.';
$string['videofile'] = 'Arquivo de vídeo';
$string['videoheader'] = 'Vídeo';
$string['videoobserve:addinstance'] = 'Adicionar uma nova atividade Video Observation';
$string['videoobserve:exportreport'] = 'Exportar relatórios do Video Observation';
$string['videoobserve:manageevents'] = 'Gerenciar a ficha de observação';
$string['videoobserve:managereference'] = 'Gerenciar observações de referência do professor';
$string['videoobserve:view'] = 'Visualizar e realizar Video Observation';
$string['videoobserve:viewreport'] = 'Visualizar relatórios agregados do Video Observation';
$string['videoobservename'] = 'Nome da atividade';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL ou ID do vídeo';
$string['watchedpercent'] = 'Percentual assistido';
