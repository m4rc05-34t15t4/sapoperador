const SIGLAS_FUNCOES = { 'hid' : 1, 'tra' : 2, 'int' : 4, 'veg' : 8, 'rec' : 16 };
$CONF = [];

async function loadJSON() {
    try {
        const response = await fetch('conf.json');
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        $data = await response.json();
        return $data;
        
    } catch (error) {
        console.error('There has been a problem with your fetch operation:', error);
    }
}

function criar_barra(usu_id){
    return `
        <div class="progress-container mt-2 ms-2 shadow">
            <div class="progress">
                <div id="progress-bar-${usu_id}" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>`;
}

function Verificar_meta_usuario(metas, dados_usu){
    if(metas != null){
        $metas_vigente = {"id_usu" : dados_usu['id'], "data-start" : null, "data_limite" : null, "qtd" : null, 'qtd_semanal' : {'total' : 0}};
        $metas_usu = JSON.parse(metas['metas_usuarios']);
        $metas_func = JSON.parse(metas['metas_funcoes']);
        $id_u = dados_usu['id'];
        $nr_f_u = dados_usu['nr_funcao'];
        if($metas_usu != null && dados_usu['id'] in $metas_usu){
            $metas_vigente["data-start"] = ("data_start" in $metas_usu[$id_u]) ? $metas_usu[$id_u]["data_start"] : metas["data_start"];
            $metas_vigente["data_limite"] = ("data_limite" in $metas_usu[$id_u]) ? $metas_usu[$id_u]["data_limite"] : metas["data_limite"];
            $metas_vigente["qtd"] = parseInt(("qtd" in $metas_usu[$id_u]) ? $metas_usu[$id_u]["qtd"] : metas["metas_qtd"]);
        }
        else if($metas_func != null && $nr_f_u in $metas_func){
            $metas_vigente["data-start"] = ("data_start" in $metas_func[$nr_f_u]) ? $metas_func[$nr_f_u]["data_start"] : metas["data_start"];
            $metas_vigente["data_limite"] = ("data_limite" in $metas_func[$nr_f_u]) ? $metas_func[$nr_f_u]["data_limite"] : metas["data_limite"];
            $metas_vigente["qtd"] = parseInt(("qtd" in $metas_func[$nr_f_u]) ? $metas_func[$nr_f_u]["qtd"] : metas["metas_qtd"]);
        }
        else{
            $metas_vigente["data-start"] = metas["data_start"];
            $metas_vigente["data_limite"] = metas["data_limite"];
            $metas_vigente["qtd"] = metas["metas_qtd"];
        }
        Object.keys(SIGLAS_FUNCOES).forEach(s => {
            $metas_vigente['qtd_semanal']['total'] += dados_usu['qtd_'+s] == null ? 0 : dados_usu['qtd_'+s];
            $metas_vigente['qtd_semanal'][s] = dados_usu['qtd_'+s];
        });
        return $metas_vigente;
    }
    else return null;
}

function criar_barra_meta(id_item, meta){
    //console.log(meta);
    $total = parseInt(meta["qtd_semanal"]["total"] / meta["qtd"] * 100);
    $(`${id_item} .qtd_meta`).attr('title', JSON.stringify(meta["qtd_semanal"])).html(`${meta["qtd_semanal"]["total"]}/${meta["qtd"]}`);
    var progressBar = $(`#progress-bar-${meta["id_usu"]}`);
    var width = $total > 100 ? 100 : $total;
    progressBar.css('width', width + '%');
    progressBar.attr('aria-valuenow', width);
    $(`${id_item} .progress-container`).attr('title', `data Limite: ${meta["data_limite"].split(" ")[0]}`);
    progressBar.text(String(width)+'%');
    if (width >= 100) progressBar.addClass('bg-success').text('Completado!');
}