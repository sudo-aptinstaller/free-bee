<template>
    <div class="container-fluid">
        <div class="row-fluid row">
            <div class="card col-2 p-3 h-100">
                <input type="text" v-model="search" class="form-control mb-2" placeholder="Search" @keyup="initiateUserSearch">
                <div class="spinner-border" role="status" v-if="searchTypingStatus == 'typing'">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div v-if="searchTypingStatus == 'done'">
                    <ul class="list-unstyled m-2">
                        <li v-for="result in results" style="cursor:pointer;" :key="result.id" @click="initiateChat(result.uuid)"><a v-if="result.uuid !== user.uuid" class="text-decoration-none shadow-none">{{result.name}}</a></li>
                    </ul>
                </div>
                <hr>
                <h5>Chats</h5>
                <ul class="list-unstyled m-2">
                    <h6 v-for="chat in chats" style="cursor:pointer;" :key="chat.id" @click="initiateChatWithUuid(chat.participation_code)"><a class="text-decoration-none shadow-none text-dark">{{chat.name}}</a></h6>
                </ul>
                <hr>
                <h5>Online</h5>
                <ul class="list-unstyled m-2">
                    <h6 v-for="user in activeUsers" :key="user.id">{{user.name}}<span class="badge bg-success text-success float-end rounded-circle">.</span></h6>
                </ul>
            </div>
            <div class="card col-9 h-100 ms-auto" v-if="activeChat !== null">
                <div class="card-body">
                    <div v-if="activeChat.participants.length > 2">
                        <div v-if="nameEditing == false">
                            <h5 @click="nameEditor(true)" style="cursor:pointer">{{activeChat.name}} <p class="float-end" v-if="userTyping == true">typing...</p></h5>
                        </div>
                        <div v-else>
                            <h5><input ref="textFieldForName" class="w-75 d-inline border-0 shadow-none text-dark" v-model="activeChat.name"><span class="float-end d-inline" style="cursor:pointer" @click="nameEditor(false)">done</span></h5>
                        </div>
                    </div>
                    <div v-else>
                        <h5>{{activeChat.participant}} <p class="float-end" v-if="userTyping == true">typing...</p></h5>
                    </div>
                    <hr>
                    <div style="min-height:75vh;margin-bottom:50px;max-height:75vh;overflow-y:scroll;-ms-overflow-style: none;scrollbar-width: none;" v-chat-scroll="{always: false, smooth: true}">
                        <div v-if="activeChat.messages.length > 0">
                          <div v-for="message in activeChat.messages" :key="message.id" :class="message.user_id == user.id ? ['bg-secondary border-0 rounded-3 shadow-none text-white float-end d-block w-75 m-2'] : ['m-2 bg-dark border-0 rounded-3 shadow-none text-white float-start d-block w-75']">
                                <div class="card-body">
                                    {{message.message}}
                                    <small class=" float-end">{{message.created_at}}</small>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="card-body mx-auto">
                                No messages yet.
                            </div>
                        </div>
                        <div class="position-absolute fixed-bottom m-2">
                            <input type="text" class="form-control d-inline" v-model="message" @change="userIsTyping" style="width:90%">
                            <button role="button" class="btn btn-success float-end" @click.prevent="sendMessage" style="width:8%">Send</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card col-9 h-100 ms-auto" v-else>
                <div class="card-body">
                    <h5 class="text-center my-auto">No active chat</h5>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props : ['userData'],
        data(){
            return{
                user : this.userData,
                chats : '',
            /**
             * Active Chat Handling
             */
                nameEditing : false,
                activeChat : null,
                message : '',
                chatChannel : '',
                userTyping : false,
                typingTimeout : null,
        
            /**
             * Active Users Handling
             */
                activeUsers : '',

            /**
             * Search Funcitonality Code
             */
                searchTypingStatus : null,
                search : '',
                results : [],
                timeout : null,
            }
        },
        created() {
            Echo.join('mymo-presence')
            .here(users => {
                this.activeUsers = users;
                this.activeUsers = this.activeUsers.filter(user => { return user.uuid !== this.user.uuid });
            })
            .joining(user => {
                this.activeUsers = [...this.activeUsers, user];
            })
            .leaving(user => {
                this.activeUsers = this.activeUsers.filter(user => { return user.uuid !== user.uuid});
            })
            .error(err => {
                console.log(err);
            });

            this.fetchUserChats();
        },
        methods: {
            fetchUserChats(){
                axios.get('fetch-user-chats')
                .then(response => {
                    this.chats = response.data;
                });
            },
            initiateUserSearch(){
                this.searchTypingStatus = 'typing';
                if(this.timeout == null){
                    this.timeout = setTimeout(()=>{
                        this.searchQueryMethod();
                        clearTimeout(this.timeout);
                    },800);
                }
            },
            initiateChat(userUuid){
                axios.get(`intiate-chat?uuid=${userUuid}`)
                .then(response => {
                    this.activeChat = response.data;
                    this.searchTypingStatus = null;
                    this.search = '';

                    this.chatChannel = Echo.join(`chat-${this.activeChat.participation_code}`);
                    
                    this.chatChannel
                    .here(users => {
                        // console.log(users);
                    })
                    .listenForWhisper('chat-name-change', (payload) =>{
                        console.log(payload);
                    })
                    .listenForWhisper('typing', (payload) => {
                        console.log(payload);
                        this.userTyping = true;
                        if(this.typingTimeout == null){
                            this.typingTimeout = setTimeout(()=>{
                                clearTimeout(this.typingTimeout);
                                this.userTyping = false;
                            }, 300);
                        }
                    })
                    .listen('UserSentAMessage', (payload) => {
                        this.activeChat.messages = [...this.activeChat.messages, payload.message];
                    })
                    .error(err => {
                        console.log(err);
                    });
                });
            },
            initiateChatWithUuid(chatUuid){
                axios.get(`chat/${chatUuid}`)
                .then(response => {
                    this.activeChat = response.data;
                    this.chatChannel = Echo.join(`chat-${this.activeChat.participation_code}`);
                    
                    this.chatChannel
                    .here(users => {
                        // console.log(users);
                    })
                    .listenForWhisper('chat-name-change', (payload) =>{
                        if(this.activeChat.participation_code == payload.chat){
                            this.activeChat.name = payload.changedName;
                            this.chats.map(chat => { if(chat.participation_code == payload.chat){ chat.name = payload.changedName; }});
                        }
                    })
                    .listenForWhisper('typing', (payload) => {
                        console.log(payload);
                        this.userTyping = true;
                        setTimeout(() => {
                            this.userTyping = false;
                        }, 1000);
                    })
                    .listen('UserSentAMessage', (payload) => {
                        this.activeChat.messages = [...this.activeChat.messages, payload.message];
                    })
                    .error(err => {
                        console.log(err);
                    });
                });
            },
            searchQueryMethod(){
                 if(this.search == (null || '')){
                    this.searchTypingStatus = null;
                    this.timeout = null;
                    return;
                }else{
                    axios.get(`search?q=${this.search}`)
                    .then(response => {
                        this.results = response.data;
                        this.searchTypingStatus = 'done'
                        this.timeout = null;
                    });
                }
            },
            nameEditor(method){
                if(method == false){
                    axios.post(`chat/${this.activeChat.participation_code}/change-name`, { name : this.activeChat.name })
                    .then(response => {
                        this.activeChat.name = response.data;
                        this.chats.map(chat => { if(chat.id == this.activeChat.id) { chat.name = this.activeChat.name; }});
                        this.nameEditing = false;
                        this.chatChannel.whisper('chat-name-change', { chat : this.activeChat.participation_code, changedName : this.activeChat.name });
                    });
                }else{
                    this.nameEditing = true;
                    setTimeout(()=>{
                        this.$refs.textFieldForName.focus();
                    },1);
                }
            },
            sendMessage(){
                axios.post(`chat/${this.activeChat.participation_code}/send-message`, { message : this.message })
                .then(response => {
                    this.activeChat.messages = [...this.activeChat.messages, response.data];
                    this.message = ''; 
                });
            },
            userIsTyping(){
                this.chatChannel.whisper('typing', { typing : true });
            }
        }
    }
</script>
