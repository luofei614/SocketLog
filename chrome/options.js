document.addEventListener('DOMContentLoaded', init, false);
function init()
{
    
    if(localStorage.getItem('online_domain'))
    {
        document.getElementById('online_domain').value=localStorage.getItem('online_domain'); 
    }

    
    if(localStorage.getItem('special_domain'))
    {
        document.getElementById('special_domain').value=localStorage.getItem('special_domain'); 
    }

    document.getElementById('save').addEventListener('click',save,false);
}


function save()
{
    localStorage.setItem('online_domain',document.getElementById('online_domain').value);
    localStorage.setItem('special_domain',document.getElementById('special_domain').value);
    alert('保存成功');
}
