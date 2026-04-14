const { Client, GatewayIntentBits } = require('discord.js');

// Configuración del bot
const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMembers // Esto permite leer quién tiene cada rol
    ] 
});

// --- AQUÍ PONES TUS IDS ---
const SERVER_ID = '123456789012345678'; // ID del servidor (Guild)
const ID_POLICIA = '111111111111111111';
const ID_MEDICO  = '222222222222222222';
const ID_BOMBERO = '333333333333333333';
const TOKEN      = 'TU_TOKEN_AQUÍ';
// --------------------------

client.once('ready', async () => {
    console.log(`✅ Bot online como: ${client.user.tag}`);

    // Acceder al servidor
    const guild = client.guilds.cache.get(SERVER_ID);
    
    if (!guild) {
        return console.error("❌ Error: No se encontró el servidor. ¿El bot está dentro?");
    }

    // Cachear/Obtener los roles
    const rolPolicia = guild.roles.cache.get(ID_POLICIA);
    const rolMedico  = guild.roles.cache.get(ID_MEDICO);
    const rolBombero = guild.roles.cache.get(ID_BOMBERO);

    console.log(`--- Estado de Roles en ${guild.name} ---`);
    console.log(`👮 Policías: ${rolPolicia ? rolPolicia.members.size : 'No encontrado'}`);
    console.log(`⚕️ Médicos: ${rolMedico ? rolMedico.members.size : 'No encontrado'}`);
    console.log(`🚒 Bomberos: ${rolBombero ? rolBombero.members.size : 'No encontrado'}`);
});

client.login(TOKEN);
